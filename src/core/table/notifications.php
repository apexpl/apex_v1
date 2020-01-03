<?php
declare(strict_types = 1);

namespace apex\core\table;

use apex\app;
use apex\libc\db;
use apex\libc\components;


class notifications 
{




    // Set columns
    public $columns = array(
    'id' => 'ID',
    'adapter' => 'Type',
    'recipient' => 'Recipient',
    'subject' => 'Subject',
    'manage' => 'Manage'
    );

    // Other variables
    public $sortable = array('id', 'adapter','recipient','subject');
    public $rows_per_page = 25;
    public $delete_button = 'Delete Checked Notifications';
    public $delete_dbtable = 'notifications';
    public $delete_dbcolumn = 'id';

    // Form field
    public $form_field = 'checkbox';
    public $form_name = 'notification_id';
    public $form_value = 'id_raw';

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
    $total = db::get_field("SELECT count(*) FROM notifications");
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
public function get_rows(int $start = 0, string $search_term = '',string $order_by = 'id'):array
{ 

    // Get SQL
    $rows = db::query("SELECT id,adapter,recipient,subject FROM notifications ORDER BY $order_by LIMIT $start,$this->rows_per_page");

    // Get rows
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

    // Load adapter
    $adapter = components::load('adapter', $row['adapter'], 'core', 'messages');

    // Format row
    $row['id_raw'] = $row['id'];
    $row['adapter'] = $adapter->display_name ?? 'Unknown';
    $row['recipient'] = method_exists($adapter, 'get_recipient_name') === true ? $adapter->get_recipient_name($row['recipient']) : $row['recipient'];
    $row['manage'] = "<center><a href=\"/admin/settings/notifications_edit?notification_id=$row[id]\" class=\"btn btn-primary btn-md\">Manage</a></center>";
    $row['id'] = '<center>' . $row['id'] . '</center>';

    // Return
    return $row;

}


}

