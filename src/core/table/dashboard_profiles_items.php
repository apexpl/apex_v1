<?php
declare(strict_types = 1);

namespace apex\core\table;

use apex\app;
use apex\libc\db;
use apex\libc\debug;

/**
 * Dashboard profile items data table.
 */
class dashboard_profiles_items
{

    // Columns
    public $columns = array(
        'type' => 'Type', 
        'package' => 'Package', 
        'alias' => 'Alias'
    );

    // Sortable columns
    public $sortable = array('id', 'type', 'alias', 'package');

    // Other variables
    public $rows_per_page = 25;
    public $has_search = false;

    // Form field (left-most column)
    public $form_field = 'checkbox';
    public $form_name = 'item_id';
    public $form_value = 'id'; 

    // Delete button
    public $delete_button = 'Delete Checked Items';
    public $delete_dbtable = 'dashboard_profiles_items';
    public $delete_dbcolumn = 'id';

/**
 * Parse attributes within <a:function> tag.
 *
 * Passes the attributes contained within the <e:function> tag that called the table.
 * Used mainly to show/hide columns, and retrieve subsets of 
 * data (eg. specific records for a user ID#).
 * 
 (     @param array $data The attributes contained within the <e:function> tag that called the table.
 */
public function get_attributes(array $data = array())
{

    $this->profile_id = $data['profile_id'];

}

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
    $total = DB::get_field("SELECT count(*) FROM dashboard_profiles_items WHERE profile_id = %i", $this->profile_id);
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
public function get_rows(int $start = 0, string $search_term = '', string $order_by = 'type,alias asc'):array 
{

    // Get rows
    $rows = DB::query("SELECT * FROM dashboard_profiles_items WHERE profile_id = %i ORDER BY $order_by LIMIT $start,$this->rows_per_page", $this->profile_id);

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

    // Format row
    $row['type'] = ucwords($row['type']);
    $row['package'] = db::get_field("SELECT name FROM internal_packages WHERE alias = %s", $row['package']);

    // Return
    return $row;

}

}

