<?php
declare(strict_types = 1);

namespace apex\core\table;

use apex\app;
use apex\libc\db;
use apex\libc\redis;


/**
 * Table that shows the login history sessions of any 
 * specified user or administrator.
 */
class auth_history 
{


    // Columns
    public $columns = array(
        'date_added' => 'Date Added',
        'logout_date' => 'Logout Date',
        'username' => 'Username',
        'ip_address' => 'IP Address',
        'manage' => 'Manage'
    );

    //// Sortable columns
    public $sortable = array('id');

    // Other variables
    public $rows_per_page = 50;
    public $has_search = false;

    // Form field (left-most column)
    public $form_field = 'none';
    public $form_name = 'auth_history_id';
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
    $this->type = $data['type'];
    $this->userid = $data['userid'] ?? 0;

    if ($this->userid > 0) { unset($this->columns['username']); }
    if (app::get_area() != 'admin') { unset($this->columns['manage']); }


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
    if ($this->userid > 0) { 
        $total = db::get_field("SELECT count(*) FROM auth_history WHERE type = %s AND userid = %i", $this->type, $this->userid);
    } else { 
        $total = db::get_field("SELECT count(*) FROM auth_history WHERE type = %s", $this->type);
    }
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
public function get_rows(int $start = 0, string $search_term = '', string $order_by = 'date_added desc'):array
{ 

    // Get rows
    if ($this->userid > 0) { 
        $rows = db::query("SELECT * FROM auth_history WHERE type = %s AND userid = %i ORDER BY $order_by LIMIT $start,$this->rows_per_page", $this->type, $this->userid);
    } else { 
        $rows = db::query("SELECT * FROM auth_history WHERE type = %s ORDER BY $order_by LIMIT $start,$this->rows_per_page", $this->type);
    }

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
    $row['logout_date'] = preg_match("/^0000/", $row['logout_date']) ? '-' : fdate($row['logout_date'], true);
    if ($this->userid == 0) { 
        $redis_hash = $this->type == 'user' ? 'users:' . $row['userid'] : 'admin:' . $row['userid'];
        $row['username'] = redis::hget($redis_hash, 'username');
    }
    $row['manage'] = "<center><a href=\"/admin/users/view_auth_session?session_id=$row[id]\" class=\"btn btn-primary btn-sm\">Manage</a></center>";

    // Return
    return $row;

}


}

