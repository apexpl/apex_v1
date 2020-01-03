<?php
declare(strict_types = 1);

namespace apex\core\table;

use apex\app;
use apex\libc\db;
use apex\app\sys\network;


class packages
{


    /**
     * @Inject
     * @var network
     */

    private $network;

    // Set columns
    public $columns = array(
    'name' => 'Name',
    'available'  => 'Available',
    'last_modified' => 'last_modified',
    'date_installed' => 'Date Installed',
    'manage' => 'Manage'
    );

    // Basic variables
    public $rows_per_page = 50;

    // Form field
    public $form_field = 'none';
    public $form_name = 'package_id';
    public $form_value = 'id';

/**
 * Passes the attributes contained within the <e:function> tag that called the 
 * table. Used mainly to show/hide columns, and retrieve subsets of data (eg. 
 * specific records for a user ID#). 
 *
 * @param array $data The attributes contained within the <e:function> tag that called the table.
 */
public function get_attributes(array $data = array())
{ 

    // Check for devkit
    $this->devkit = $data['devkit'] ?? 0;
    $this->upgrades = array();

    // Check for upgrades, if not devkig
    if ($this->devkit == 0) { 
        unset($this->columns['manage']);

        // Check for upgrades
        $client = $this->network;
        $this->upgrades = $client->check_upgrades();

    }

}

/**
 * Get the total number of rows available for this table. This is used to 
 * determine pagination links. 
 *
 * @param string $search_term Only applicable if the AJAX search box has been submitted, and is the term being searched for.
 *
 * @return int The total number of rows available for this table.
 */
public function get_total(string $search_term = ''):int
{ 

    // Get total
    $total = db::get_field("SELECT count(*) FROM internal_packages");
    if ($total == '') { $total = 0; }

    // Return
    return (int) $total;

}

/**
 * Gets the actual rows to display to the web browser. Used for when initially 
 * displaying the table, plus AJAX based search, sort, and pagination. 
 *
 * @param int $start The number to start retrieving rows at, used within the LIMIT clause of the SQL statement.
 * @param string $search_term Only applicable if the AJAX based search base is submitted, and is the term being searched form.
 * @param string $order_by Must have a default value, but changes when the sort arrows in column headers are clicked.  Used within the ORDER BY clause in the SQL statement.
 *
 * @return array An array of associative arrays giving key-value pairs of the rows to display.
 */
public function get_rows(int $start = 0, string $search_term = '', string $order_by = 'name asc'):array
{ 

    // Get rows
    $rows = db::query("SELECT * FROM internal_packages ORDER BY $order_by LIMIT $start,$this->rows_per_page");


    // Go through rows
    $results = array();
    foreach ($rows as $row) { 
        array_push($results, $this->format_row($row));
    }

    // Return
    return $results;

}

/**
 * Retrieves raw data from the database, which must be formatted into user 
 * readable format (eg. format amounts, dates, etc.). 
 *
 * @param array $row The row from the database.
 *
 * @return array The resulting array that should be displayed to the browser.
 */
public function format_row(array $row):array
{ 

// Check if upgrade available
    if (isset($this->upgrades[$row['alias']])) { 
        $available = 'v' . $this->upgrades[$row['alias']];
        list($prefix, $suffix) = array('<b>', '</b>');
    } else { 
        list($available, $prefix, $suffix) = array('', '', '');
    }
    $last_modified = $row['last_modified'] ?? '';


    // Format row
    $row['name'] = $prefix . $row['name'] . ' v' . $row['version'] . $suffix;
    $row['available'] = $prefix . $available . $suffix;
    $row['last_modified'] = !preg_match("/^2/", $last_modified) ? '-' : $prefix . fdate($last_modified, true) . $suffix;
    $row['date_installed'] = $prefix . fdate($row['date_installed'], true) . $suffix;
    $row['manage'] = "<center><a href=\"/admin/devkit/packages_manage?package=$row[alias]\" class=\"btn btn-primary btn-sm\">Manage</a></center>";

    // Return
    return $row;

}


}

