<?php
declare(strict_types = 1);

namespace apex\core\service;

use apex\app;
use apex\libc\db;
use apex\libc\redis;
use apex\libc\debug;
use apex\libc\components;
use apex\app\exceptions\ComponentException;

/**
 * Handles all overall scheduled tasks for 
 * Apex, including adding, removing, and running tasks.
 */
class tasks
{

    /**
     * Optionally define a BASE64 encoded string here, and this will be used as the default code 
     * for all adapters created for this service.
     * 
     * Use the merge field http_requests 
     * for the class name, and it will be replaced appropriately.
     */
    public $default_code = 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XGNvcmVcc2VydmljZVx0YXNrczsKCnVzZSBhcGV4XGFwcDsKdXNlIGFwZXhcbGliY1xkYjsKdXNlIGFwZXhcbGliY1xkZWJ1ZzsKdXNlIGFwZXhcbGliY1xjb21wb25lbnRzOwp1c2UgYXBleFxjb3JlXHNlcnZpY2VcdGFza3M7CnVzZSBhcGV4XGFwcFxpbnRlcmZhY2VzXFRhc2tJbnRlcmZhY2U7CgovKioKICogQWRhcGF0ZXIgY2xhc3MgdGhhdCBoYW5kbGVzIGFsbCBtZXRob2RzIGZvciB0aGUgCiAqIHRhc2suCiAqLwpjbGFzcyB+YWxpYXN+IGV4dGVuZHMgdGFza3MgaW1wbGVtZW50cyBUYXNrSW50ZXJmYWNlCnsKCiAgICAvLyBQcm9wZXJ0aWVzCiAgICBwdWJsaWMgJG5hbWUgPSAnfmFsaWFzfic7CgoKLyoqCiAqIEV4ZWN1dGUgdGhlIHRhc2sKICoKICogQHBhcmFtIHN0cmluZyAkY3Jvbl9hbGlhcyBUaGUgYWxpYXMgb2YgdGhlIHRhc2suCiAqIEBwYXJhbSBzdHJpbmcgJGRhdGEgVGhlIG9wdGlvbmFsIGRhdGEgb2YgdGhlIHRhc2suCiAqCiAqIEByZXR1cm4gYm9vbCBXSGV0aGVyIG9yIG5vdCB0aGUgb3BlcmF0aW9uIGNvbXBsZXRlZCBzdWNjZXNzZnVsbHkuCiAqLwpwdWJsaWMgZnVuY3Rpb24gcHJvY2VzcyhzdHJpbmcgJGNyb25fYWxpYXMsIHN0cmluZyAkZGF0YSA9ICcnKTpib29sCnsKCgogICAgLy8gUmV0dXJuCiAgICByZXR1cm4gdHJ1ZTsKCn0KCi8qKgogKiBHZXQgYWxsIGF2YWlsYWJsZSB0YXNrcwogKiB0aGUgb25lLXRpbWUgZXhlY3V0aW9uIG9mIGEgdGFzayB3aXRoaW4gdGhlIGFkbWluIHBhbmVsLgogKiAKICogQHJldHVybiBhcnJheSBBbiBhc3NvY2lhdGl2ZSBhcnJheSBvZiBhbGwgYXZhaWxhYmxlIHRhc2tzLCB2YWx1ZXMgYmVpbmcgdGhlIGRpc3BsYXkgbmFtZSBpbiBicm93c2VyLgogKi8KcHVibGljIGZ1bmN0aW9uIGdldF9hdmFpbGFibGVfdGFza3MoKTphcnJheQp7CiAgICAvLyBHZXQgb3B0aW9ucwogICAgJG9wdGlvbnMgPSBhcnJheSgpOwoKICAgIC8vIFJldHVybgogICAgcmV0dXJuICRvcHRpb25zOwoKfQoKLyoqCiAqIEdldCBuYW1lIG9mIGEgdGFzawogKgogKiBAcGFyYW0gc3RyaW5nICRhbGlhcyBUaGUgYWxpYXMgb2YgdGhlIHRhc2suCiAqCiAqIEByZXR1cm4gc3RyaW5nIFRoZSBuYW1lIG9mIHRoZSB0YXNrLgogKi8KcHVibGljIGZ1bmN0aW9uIGdldF9uYW1lKHN0cmluZyAkYWxpYXMpOnN0cmluZwp7CgogICAgLy8gR2V0IG5hbWUKICAgICRuYW1lID0gJGFsaWFzOwoKICAgIC8vIFJldHVybgogICAgcmV0dXJuICRuYW1lOwp9Cgp9CgoK';


/**
 * Add new scheduled task.
 *
 * @param string $adapter The adapter of the task.
 * @param string $alias The alias of the ask.
 * @param string $execute_time The datetime to execute the task, formatted in YYY-MM-DD HH:II:SS
 * @param string $data Any optional data to include with the task.
 *
 * @return int The unique ID# of the newly scheduled task.
 */
public function add(string $adapter, string $alias, string $execute_time = '', string $data = '')
{

    // Initialize
    if ($execute_time == '') { $execute_time = db::get_field("SELECT now()"); }
    if (!$client = components::load('adapter', $adapter, 'core', 'tasks')) { 
        throw new ComponentException('no_load', 'adapter', '', $adapter, 'core', 'tasks');
    }

    // Add to database
    db::insert('internal_tasks', array(
        'execute_time' => $execute_time, 
        'adapter' => $adapter, 
        'alias' => $alias, 
        'data' => $data)
    );
    $task_id = db::insert_id();

    // Debug
    debug::add(1, tr("Added new scheduled task, adapter: {1}, alias: {2}, execute_time: {3}", $adapter, $alias, $execute_time));

    // Return
    return $task_id;

}

/**
 * Remove a scheduled task
 * 
 * @param int $task_id Optional ID# of the specific task to remove
 * @param string $adapter optional adapter of the task to remove.
 * @param string $alias Optional alias of the task to remove.
 */
public function remove(int $task_id = 0, string $adapter = '', string $alias = '')
{

    // Remove tasks
    if ($task_id > 0) { 
        db::query("DELETE FROM internal_tasks WHERE id = %i", $task_id);
    } else { 
        db::query("DELETE FROM internal_tasks WHERE adapter = %s AND alias = %s", $adapter, $alias);
    }

    // Debug
    debug::add(1, tr("Deleted scheduled tasks with info, id: {1}, adapter: {2}, alias: {3}", $task_id, $adapter, $alias));

}

/**
 * Run a scheduled task
 *
 * @param string $adapter The adapater alias of the task.
 * @param string $alias The alias of the task.
 * @param string $data Optional data of the task.
 * @param int $task_id Optional unique ID# of the task within the 'internal_tasks' table.
 */
public function run(string $adapter, string $alias, string $data = '', int $task_id = 0)
{

    // load task
    if (!$task = components::load('adapter', $adapter, 'core', 'tasks')) {  
        return false;
    }

    // Execute task
    if (!$task->process($alias, $data)) { 
        return false;
    }

    // Delete task
    if ($task_id > 0) { 
        db::query("DELETE FROM internal_tasks WHERE id = %i", $task_id);
    }

}

/**
 * Create select options
 *
 * @param string $selected Optional selected task.
 *
 * @return string The resulting HTML options for all available tasks
 */
public function create_options(string $selected = ''):string
{

    // Initialize variables
    $options = '';
    $last_adapter = '';

    // Go through adapters
    $adapters = db::get_column("SELECT alias FROM internal_components WHERE type = 'adapter' AND package = 'core' AND parent = 'tasks' ORDER BY alias");
    foreach ($adapters as $alias) { 

        // Load adapater
        if (!$adapter = components::load('adapter', $alias, 'core', 'tasks')) { 
            continue;
        }
        $name = $adapter->name ?? ucwords(str_replace('_', ' ', $alias));

        // Get options
        $tasks = $adapter->get_available_tasks();
        if (count($tasks) == 0) { continue; }

        // Add / close optgroup, as needed
        if ($last_adapter != '') { $options .= "</optgroup>"; }
        $options .= "<optgroup name=\"$name\">";
        $last_adapter = $alias;

        // Go through options
        foreach ($tasks as $key => $value) { 
            $key = $alias . ':' . $key;
            $chk = $key == $selected ? 'selected="selected"' : '';
            $options .= "<option value=\"$key\" $chk>$value</option>";
        }
    }

    // Return
    return $options;

}

}


