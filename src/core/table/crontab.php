<?php
declare(strict_types = 1);

namespace apex\core\table;

use apex\app;
use apex\libc\db;
use apex\libc\components;
use apex\libc\date;


/**
 * Data table for all crontab jobs.
 */
class crontab 
{


    // Set columns
    public $columns = array(
        'name' => 'Name',
        'autorun' => 'Active',
        'time_interval' => 'Interval',
        'nextrun_time' => 'Next Execution'
    );

    // Basic variables
    public $has_search = false;
    public $rows_per_page = 50;

    // Form field
    public $form_field = 'checkbox';
    public $form_name = 'cron_alias';
    public $form_value = 'cron_alias';

/**
 * Get total number of rows. 
 *
 * Obtains the total number of rows within the table, and is used to create 
 * the necessary pagination link. 
 *
 * @param string $search_term Only applicable if the AJAX search box has been submitted, and is the term being searched for.
 *
 * @return int The total number of rows available for this table.
 */
public function get_total(string $search_term = ''):int
{ 

    // Get total
    $total = db::get_field("SELECT count(*) FROM internal_components WHERE type = 'crontab'");
    if ($total == '') { $total = 0; }

    // Return
    return (int) $total;

}

/**
 * Get table rows to display. 
 *
 * Gathers and formats the exact table rows to display within the web browser. 
 * This method is called when initially viewing the template, plus for AJAX 
 * based search, pagination, and sorting. 
 *
 * @param int $start The number to start retrieving rows at, used within the LIMIT clause of the SQL statement.
 * @param string $search_term Only applicable if the AJAX based search base is submitted, and is the term being searched form.
 * @param string $order_by Must have a default value, but changes when the sort arrows in column headers are clicked.  Used within the ORDER BY clause in the SQL statement.
 *
 * @return array An array of associative arrays giving key-value pairs of the rows to display.
 */
public function get_rows(int $start = 0, string $search_term = '', string $order_by = 'display_name asc'):array
{ 

// Go through crontab jobs
    $results = array();
    $rows = db::query("SELECT * FROM internal_components WHERE type = 'cron' ORDER BY package,alias");
    foreach ($rows as $row) { 

        // Load component
        if (!$cron = components::load('cron', $row['alias'], $row['package'])) { 
            continue;
        }
        $autorun = $cron->autorun ?? 1;
        $interval = $cron->time_interval ?? '';

        // Get nextrun time
        if ($execute_time = db::get_field("SELECT execute_time FROM internal_tasks WHERE adapter = 'crontab' AND alias = %s", $row['package'] . ':' . $row['alias'])) { 
        $execute_time = fdate($execute_time, true);
        } else { 
            $execute_time = 'Not Scheduled';
        }

        // Set vars
        $vars = array(
            'cron_alias' => $row['package'] . ':' . $row['alias'], 
            'name' => ($cron->name ?? ucwords(str_replace('_', ' ', $row['alias']))), 
            'autorun' => ($autorun == 1 ? 'Yes' : 'No'), 
            'time_interval' => date::parse_date_interval($interval), 
            'nextrun_time' => $execute_time
        );
        $results[] = $vars;
    }

    // Return
    return $results;

}

}


