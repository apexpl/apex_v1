<?php
declare(strict_types = 1);

namespace apex\core\table;

use apex\app;
use apex\svc\db;
use apex\svc\io;


class cms_pages 
{




    // Columns
    public $columns = array(
    'uri' => 'URI',
    'layout' => 'Layout',
    'title' => 'Title'
    );

    //// Sortable columns
    public $sortable = array('id');

    // Other variables
    public $rows_per_page = 25;
    public $has_search = false;

    // Form field (left-most column)
    public $form_field = 'none';
    public $form_name = 'cms_pages_id';
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

    // Get files
    $files = io::parse_dir(SITE_PATH . '/views/tpl/' . $this->area);

    // Get existing pages
    $pages = array();
    $rows = db::query("SELECT * FROM cms_pages WHERE area = %s", $this->area);
    foreach ($rows as $row) { 
        $pages[$row['filename']] = array(
            'layout' => $row['layout'],
            'title' => $row['title']
        );
    }

    // Get available layouts
    $var = $this->area == 'members' ? 'users:theme_members' : 'core:theme_public';
    $theme_dir = SITE_PATH . '/views/themes/' . app::_config($var) . '/layouts';
    $layout_files = io::parse_dir($theme_dir);

    // Go through layouts
    $layouts = array();
    foreach ($layout_files as $file) { 
        $file = preg_replace("/\.tpl$/", "", $file);
        $layouts[$file] = ucwords(str_replace("_", " ", $file));
    }

    // Go through files
    $results = array();
    foreach ($files as $file) { 
        $file = preg_replace("/\.tpl$/", "", $file);

        if (isset($pages[$file])) { 
            $layout = $pages[$file]['layout'];
            $title = $pages[$file]['title'];
        } else { 
            $layout = 'default';
            $title = '';
        }

        // Layout options
        $layout_options = '';
        foreach ($layouts as $lfile => $name) { 
            $chk = $lfile == $layout ? 'selected="selected"' : '';
            $layout_options .= "<option value=\"$lfile\" $chk>$name</option>";
        }

        $vars = array(
            'uri' => '/' . $file,
            'layout' => "<select name=\"layout_" . $this->area . '_' . $file . "\">$layout_options</select>",
            'title' => "<input type=\"text\" name=\"title_" . $this->area . '_' . $file . "\" size=\"20\" value=\"$title\">"
        );
        array_push($results, $vars);
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


    // Return
    return $row;

}


}

