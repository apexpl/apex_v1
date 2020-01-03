<?php
declare(strict_types = 1);

namespace apex\core\service\tasks;

use apex\app;
use apex\libc\db;
use apex\libc\debug;
use apex\libc\components;
use apex\libc\date;
use apex\core\service\tasks;
use apex\app\interfaces\TaskInterface;

/**
 * Adapater that handles executing of all individual 
 * crontab jobs, either automated or one-time.
 */
class crontab extends tasks implements TaskInterface
{

    // Properties
    public $name = 'Crontab Job';


/**
 * Execute the task
 *
 * @param string $cron_alias The alias of the task.
 * @param string $data The optional data of the task.
 *
 * @return bool WHether or not the operation completed successfully.
 */
public function process(string $cron_alias, string $data = ''):bool
{

    // Initialize
    list($package, $alias) = explode(':', $cron_alias);

    // Add log
    $log_line = '[' . date('Y-m-d H:i:s') . '] Starting (' . $cron_alias . ")\n";
    file_put_contents(SITE_PATH . '/storage/logs/cron.log', $log_line, FILE_APPEND);

    // Load crontab job
    if (!$cron = components::load('cron', $alias, $package)) { 
        return false;
    }

    // Execute crontab job
    components::call('process', 'cron', $alias, $package);

    // Schedule again, if needed
    $autorun = $cron->autorun ?? 1;
    if ($autorun == 1 && isset($cron->time_interval) && preg_match("/^\w\d+$/", $cron->time_interval)) { 
        $execute_time = date::add_interval($cron->time_interval); 
        $this->add('crontab', $cron_alias, $execute_time, $data);
    }

    // Add log
    $log_line = '[' . date('Y-m-d H:i:s') . '] Completed (' . $cron_alias . ")\n";
    file_put_contents(SITE_PATH . '/storage/logs/cron.log', $log_line, FILE_APPEND);

    // Return
    return true;

}

/**
 * Get all available tasks
 * the one-time execution of a task within the admin panel.
 * 
 * @return array An associative array of all available tasks, values being the display name in browser.
 */
public function get_available_tasks():array
{

    // Go through all crontab components
    $options = array();
    $rows = db::query("SELECT alias,package FROM internal_components WHERE type = 'cron' ORDER BY package,alias");
    foreach ($rows as $row) { 

        // Load cron job
        if (!$cron = components::load('cron', $row['alias'], $row['package'])) { 
            continue;
        }

        // Add to options
        $cron_alias = $row['package'] . ':' . $row['alias'];
        $options[$cron_alias] = $cron->name ?? ucwords(str_replace('_', ' ', $row['alias']));
    }

    // Return
    return $options;

}

/**
 * Get name of a task
 *
 * @param string $alias The alias of the task.
 *
 * @return string The name of the task.
 */
public function get_name(string $cron_alias):string
{

// Load crontab job
    list($package, $alias) = explode(':', $cron_alias, 2);
    if (!$cron = components::load('cron', $alias, $package)) { 
        $name = ucwords(str_replace('_', ' ', $alias));
    } else { 
        $name = $cron->name ?? ucwords(str_replace('_', ' ', $alias));
    }

    // Return
    return $name;
}

}


