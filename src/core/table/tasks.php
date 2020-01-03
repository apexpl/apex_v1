<?php
declare(strict_types = 1);

namespace apex\core\table;

use apex\app;
use apex\libc\db;
use apex\libc\debug;
use apex\libc\components;


/**
 * Data table listing all pending tasks that 
 * scheduled.  Displays within the 
 * Maintenance->Tasks Manager menu of admin panel.
 */
class tasks
{

    // Columns
    public $columns = array(
        'adapter' => 'Type', 
        'name' => 'Name', 
        'execute_time' => 'Run Time'
    );

    // Sortable columns
    public $sortable = array('id', 'adapter', 'execute_time');

    // Other variables
    public $rows_per_page = 50;
    public $has_search = false;

    // Form field (left-most column)
    public $form_field = 'checkbox';
    public $form_name = 'task_id';
    public $form_value = 'id'; 

    // Delete button
    public $delete_button = 'Delete Checked Tasks';
    public $delete_dbtable = 'internal_tasks';
    public $delete_dbcolumn = 'id';


/**
 * Get total rows.
 *
 * Get the total number of rows available for this table.
 * This is used to determine pagination links.
 * 
 *     @param string $search_term Only applicable if the AJAX search box has been submitted, and is the term being searched for.
 *     @return int The total number of rows available for this table.
 */
public function get_total(string $search_term = ''):int 
{

    // Get total
    $total = DB::get_field("SELECT count(*) FROM internal_tasks");
    if ($total == '') { $total = 0; }

    // Return
    return (int) $total;

}

/**
 * Get rows to display
 *
 * Gets the actual rows to display to the web browser.
 * Used for when initially displaying the table, plus AJAX based search, 
 * sort, and pagination.
 *
 *     @param int $start The number to start retrieving rows at, used within the LIMIT clause of the SQL statement.
 *     @param string $search_term Only applicable if the AJAX based search base is submitted, and is the term being searched form.
 *     @param string $order_by Must have a default value, but changes when the sort arrows in column headers are clicked.  Used within the ORDER BY clause in the SQL statement.
 *
 *     @return array An array of associative arrays giving key-value pairs of the rows to display.
 */
public function get_rows(int $start = 0, string $search_term = '', string $order_by = 'id asc'):array 
{

    // Get rows
    $rows = DB::query("SELECT * FROM internal_tasks ORDER BY $order_by LIMIT $start,$this->rows_per_page");

    // Go through rows
    $results = array();
    foreach ($rows as $row) { 
        array_push($results, $this->format_row($row));
    }

    // Return
    return $results;

}

/**
 * Format a single row.
 *
 * Retrieves raw data from the database, which must be 
 * formatted into user readable format (eg. format amounts, dates, etc.).
 *
 *     @param array $row The row from the database.
 *
 *     @return array The resulting array that should be displayed to the browser.
 */
public function format_row(array $row):array 
{

    // Load adapter
    $adapter = components::load('adapter', $row['adapter'], 'core', 'tasks');

    // Format row
    $row['adapter'] = $adapter->name ?? ucwords(str_replace('_', ' ', $row['adapter']));
    $row['name'] = $adapter->get_name($row['alias']);
    $row['execute_time'] = fdate($row['execute_time'], true);

    // Return
    return $row;

}

}

