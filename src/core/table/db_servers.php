<?php
declare(strict_types = 1);

namespace apex\core\table;

use apex\app;
use apex\svc\redis;


class db_servers 
{




    // Columns
    public $columns = array(
    'type' => 'Type',
    'dbhost' => 'Host',
    'dbuser' => 'Username',
    'manage' => 'Manage'
    );

    // Other variables
    public $rows_per_page = 25;
    public $has_search = false;

    // Form field (left-most column)
    public $form_field = 'checkbox';
    public $form_name = 'db_server_id';
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
    $total = redis::llen('config:db_slaves');
    $total++;

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
public function get_rows(int $start = 0, string $search_term = '', string $order_by = 'id asc'):array
{ 

    // Get master DB
    $vars = redis::hgetall('config:db_master');
    $vars['id'] = 'master';
    $vars['type'] = 'Master';
    $row['manage'] = "<center><a href=\"/admin/settings/general_db_manage?server_id=$vars[id]\" class=\"btn btn-primary btn-sm\">Manage</a></center>";
    $results = array($vars);

    // Go through slaves
    $num = 0;
    $rows = redis::lrange('config:db_slaves', 0, -1);
    foreach ($rows as $row) { 
        $row = json_decode($row, true);
        $row['id'] = $num;
        $row['type'] = 'Slave';
        $row['manage'] = "<center><a href=\"/admin/settings/general_db_manage?server_id=$row[id]\" class=\"btn btn-primary btn-sm\">Manage</a></center>";
        array_push($results, $row);
    $num++; }

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


    // Return
    return $row;

}


}

