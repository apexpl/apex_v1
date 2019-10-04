<?php
declare(strict_types = 1);

namespace apex\core\cli;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\svc\log;
use apex\svc\components;
use apex\svc\date;


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
public function process(...$args)
{ 

    $this->check_pids();

    // Go through cron jobs
    $secs = time();
    $rows = db::query("SELECT * FROM internal_crontab WHERE autorun = 1 AND nextrun_time < $secs ORDER BY nextrun_time");
    foreach ($rows as $row) { 

        // Check failed
        db::query("UPDATE internal_crontab SET failed = failed + 1 WHERE id = %i", $row['id']);
        if ($row['failed'] >= 5) { 
            db::query("UPDATE internal_crontab SET nextrun_time = %i WHERE id = %i", (time() + 10800), $row['id']);
            log::critical(tr("Crontab job failed five or more times, package: {1}, alias: {2}", $row['package'], $row['alias']));
        }

        // Load crontab job
        if (!$cron = components::load('cron', $row['alias'], $row['package'])) { 
            continue;
        }

        // Add log
        $log_line = '[' . date('Y-m-d H:i:s') . '] Starting (' . $row['package'] . ':' . $row['alias'] . ")\n";
        file_put_contents(SITE_PATH . '/storage/logs/cron.log', $log_line, FILE_APPEND);

        // Execute cron job
        $cron->process();

        // Set variables
        $name = isset($cron->name) ? $cron->name : $row['alias'];
        $next_date = date::add_interval($cron->time_interval, time(), false);

        // Update crontab job times
        db::update('internal_crontab', array(
            'failed' => 0, 
            'time_interval' => $cron->time_interval, 
            'display_name' => $name, 
            'nextrun_time' => $next_date, 
            'lastrun_time' => time()), 
        "id = %i", $row['id']);

        // Add log
        $log_line = '[' . date('Y-m-d H:i:s') . '] Completed (' . $row['package'] . ':' . $row['alias'] . ")\n";
        file_put_contents(SITE_PATH . '/storage/logs/cron.log', $log_line, FILE_APPEND);
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

