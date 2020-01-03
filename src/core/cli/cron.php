<?php
declare(strict_types = 1);

namespace apex\core\cli;

use apex\app;
use apex\libc\db;
use apex\libc\debug;
use apex\libc\log;
use apex\libc\components;
use apex\libc\date;


/**
 * Class to handle the custom CLI command as necessary. 
 */
class cron
{


/**
 * Executes the CLI command. 
 *
 * @param iterable $args The arguments passed to the command cline.
 */
public function process($args)
{ 

    // Process individual jobs, if exist
    if (count($args) > 0) { 

        foreach ($args as $cron_alias) { 
            if (!preg_match("/^(.+?):(.+)$/", $cron_alias, $match)) { 
                continue;
            }

            // Process job
            components::call('process', 'cron', $match[2], $match[1]);
        }

        // Return
        return "Successfully executed specified crontab jobs\n";
    }

    $this->check_pids();

    // Initialize
    $service = components::load('service', 'tasks', 'core');

    // Go through scheduled tasks
    $rows = db::query("SELECT * FROM internal_tasks WHERE execute_time <= now() ORDER BY execute_time");
    foreach ($rows as $row) { 

        // Check failed
        db::query("UPDATE internal_tasks SET failed = failed + 1 WHERE id = %i", $row['id']);
        if ($row['failed'] >= 5) { 
            db::query("UPDATE internal_tasks SET execute_time = date_add(now() interval 2 hour) WHERE id = %i", $row['id']);
            log::critical(tr("Scheduled task job failed five or more times, adapter: {1}, alias: {2}", $row['adapter'], $row['alias']));
        }

        // Run task
        $service->run($row['adapter'], $row['alias'], $row['data'], (int) $row['id']);
    }

}

/**
    * Check existing PIds
 *
 * Check the current processed, and see whether or not 
 * RPC / web socket servers are running, and start them if not.  Also exists if a current crontab 
 * process is still running.
 */
private function check_pids()
{

    // Get list of processes
    $lines = array();
    exec('ps auxw | grep apex', $lines);

    // Check processes
    list($found_cron, $found_rpc, $found_websocket) = array(false, false, false);
    foreach ($lines as $line) { 
        $vars = preg_split("/(\s+)/", $line);
        $chk = $vars[12] ?? '';

        if ($chk == 'core.cron' && $vars[1] != getmypid()) { $found_cron = true; }
        if ($chk == 'core.rpc') { $found_rpc = true; }
        if ($chk == 'core.websocket') { $found_websocket = true; }
    }




    // Restart Apex daemons, if needed
    if ($found_rpc === false || $found_websocket === false) { 
        exec(SITE_PATH . "/bootstrap/apex restart");
    }

    // Exit, if cron is running
    if ($found_cron === true) { 
        echo "Cron already running, exiting\n";
        exit(0);
    }

}

}

