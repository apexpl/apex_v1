<?php
declare(strict_types = 1);

namespace apex\core\table;

use apex\app;
use apex\libc\db;


class cms_menus 
{




    // Columns
    public $columns = array(
        'delete' => 'Delete',
        'uri' => 'URI',
        'order' => 'Order',
        'display_name' => 'Title',
        'is_active' => 'Active',
        'manage' => 'Manage'
    );

    // Sortable columns
    //public $sortable = array('id');

    // Other variables
    public $rows_per_page = 100;
    public $has_search = false;

    // Form field (left-most column)
    public $form_field = 'none';
    public $form_name = 'cms_menus_id';
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
    $this->area = $data['area'];
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
    return 1;
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
    // Set vars
    $prefix = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

    // Get parent menus
    $results = array();
    $rows = db::query("SELECT * FROM cms_menus WHERE area = %s AND parent = '' ORDER BY order_num", $this->area);
    foreach ($rows as $row) { 

        // Set vars
        $row['uri'] = '/' . $row['alias'];
        $row['order'] = "<input type=\"text\" name=\"order_" . $row['id'] . "\" value=\"$row[order_num]\" style=\"width: 50px;\">";
        $row['delete'] = $row['is_system'] == 1 ? '' : "<center><input type=\"checkbox\" name=\"delete[]\" value=\"$row[id]\"></center>";
        $chk = $row['is_active'] == 1 ? 'checked="checked"' : '';
        $row['is_active'] = "<center><input type=\"checkbox\" name=\"is_active[]\" value=\"$row[id]\" $chk></center>";
        $row['manage'] = "<center><a href=\"/admin/cms/menus_manage?menu_id=$row[id]\" class=\"btn btn-primary btn-sm\">Manage</a></center>";
        array_push($results, $row);

        // Go through child menus
        $crows = db::query("SELECT * FROM cms_menus WHERE area = %s AND parent = %s ORDER BY order_num", $this->area, $row['alias']);
        foreach ($crows as $crow) { 

            // Set variables
            $crow['uri'] = $prefix . '/' . $row['alias'] . '/' . $crow['alias'];
            $crow['order'] = $prefix . "<input type=\"text\" name=\"order_" . $crow['id'] . "\" value=\"$crow[order_num]\" style=\"width: 50px;\">";
            $crow['delete'] = $crow['is_system'] == 1 ? '' : "<center><input type=\"checkbox\" name=\"delete[]\" value=\"$crow[id]\"></center>";
            $chk = $crow['is_active'] == 1 ? 'checked="checked"' : '';
            $crow['is_active'] = "<center><input type=\"checkbox\" name=\"is_active[]\" value=\"$crow[id]\" $chk></center>";
            $crow['manage'] = "<center><a href=\"/admin/cms/menus_manage?menu_id=$crow[id]\" class=\"btn btn-primary btn-sm\">Manage</a></center>";
            array_push($results, $crow);
        }
    }

    // Return
    return $results;


}



}

