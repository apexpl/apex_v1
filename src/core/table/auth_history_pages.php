<?php
declare(strict_types = 1);

namespace apex\core\table;

use apex\app;
use apex\svc\db;


class auth_history_pages 
{




    // Columns
    public $columns = array(
    'date_added' => 'Date',
    'request_method' => 'Method',
    'uri' => 'URI',
    'view' => 'View Request'
    );

    // Sortable columns
    //public $sortable = array('id');

    // Other variables
    public $rows_per_page = 100;
    public $has_search = false;

    // Form field (left-most column)
    public $form_field = 'none';
    public $form_name = 'auth_history_pages_id';
    public $form_value = 'id';

    /**
     * Process attributes passed to function tag 
     *
     * Passes all attributes passed within the <a:function> tag in the TPL 
     * template, and is used for things such as to show / hide templates, or set 
     * variables to be used within the WHERE clause of the SQL statements, etc. 
     *
     * @param array $data The attributes contained within the <e:function> tag that called the table.
     */




public function get_attributes(array $data = array())
{ 
    $this->history_id = $data['history_id'];
}

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
$total = db::get_field("SELECT count(*) FROM auth_history_pages WHERE history_id = %i", $this->history_id);
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
public function get_rows(int $start = 0, string $search_term = '', string $order_by = 'date_added asc'):array
{ 

    // Get rows
    $rows = db::query("SELECT * FROM auth_history_pages WHERE history_id = %i ORDER BY $order_by LIMIT $start,$this->rows_per_page", $this->history_id);

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
    $row['date_added'] = fdate($row['date_added'], true);
    $row['view'] = "<center><a href=\"/admin/users/view_auth_session_request?request_id=$row[id]\" target=\"_blank\" class=\"btn btn-primary btn-sm\">View Request</a></center>";

    // Return
    return $row;

}


}

