<?php
declare(strict_types = 1);

namespace apex\core\table;

use apex\app;
use apex\svc\db;


class crontab 
{




    // Set columns
    public $columns = array(
    'display_name' => 'Name',
    'autorun' => 'Active',
    'time_interval' => 'Interval',
    'lastrun_time' => 'Last Executed',
    'nextrun_time' => 'Next Execution'
    );

    // Basic variables
    public $has_search = false;
    public $rows_per_page = 50;

    // Form field
    public $form_field = 'checkbox';
    public $form_name = 'crontab_id';
    public $form_value = 'id';

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
    $total = db::get_field("SELECT count(*) FROM internal_crontab");
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

    // Get rows
    $rows = db::query("SELECT * FROM internal_crontab ORDER BY $order_by LIMIT $start,$this->rows_per_page");

    // Go through rows
    $results = array();
    foreach ($rows as $row) { 
        array_push($results, $this->format_row($row));
    }

    // Return
    return $results;

}

/**
 * Formats a single row for display within the web browser. 
 *
 * Has one database table row passed to it, an associative array, which can 
 * then be formatted as necessary for display within the web brwoser.  This 
 * takes the raw contents from the database and converts it to displable 
 * format. 
 *
 * @param array $row The row from the database.
 *
 * @return array The resulting array that should be displayed to the browser.
 */
public function format_row(array $row):array
{ 

    // Format row
    $row['autorun'] = $row['autorun'] == 1 ? 'Yes' : 'No';
    $row['nextrun_time'] = fdate(date('Y-m-d H:i:s', (int) $row['nextrun_time']), true);
    $row['lastrun_time'] = fdate(date('Y-m-d H:i:s', (int) $row['lastrun_time']), true);



    // Return
    return $row;

}


}

