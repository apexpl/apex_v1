<?php
declare(strict_types = 1);

namespace apex\app\web;

use apex\app;
use apex\libc\debug;
use apex\libc\components;
use apex\app\utils\tables;
use apex\app\exceptions\ComponentException;


/**
 * Handles all AJAX functionality, allowing DOM elements within the web 
 * browser to be modified without writing any Javascript. 
 */
class ajax
{




    public $results = array();

/**
 * The one required method within the AJAX component classes. 
 */
public function process()
{ 
    return true;
}

/**
 * Add AJAX action 
 *
 * Adds an entry to the self::$actions array.  Upon request completion, this 
 * array is formatted into JSON, and returned to the browser for processing 
 * via Javascript. 
 *
 * @param string $action The action to perform within the web browser.
 * @param array $vars An array containing all necessary variables, different for each action.
 */
final protected function add(string $action, array $vars)
{ 

    $vars['action'] = $action;
    array_push($this->results, $vars);

    // Debug
    debug::add(5, tr("AJAX action added: {1}, variables: {2}", $action, serialize($vars)));

}


/**
 * Opens a dialog in the browser containing the provided message via the 
 * Javascript alert() function. 
 *
 * @param string $message The message to display.
 */
final public function alert(string $message)
{ 
    $this->add('alert', array('message' => $message));
}

/**
 * Deletes all rows within the <tbody> of specified table. ( Used for 
 * pagination, quick search, and sorting rows via AJAX. 
 *
 * @param string $divid The element ID of the table.
 */
final public function clear_table(string $divid)
{ 
    $this->add('clear_table', array('divid' => $divid));
}

/**
 * Removes all rows that contain a checked checkbox from the <tbody> of the 
 * specified table. 
 *
 * @param string $divid The element ID of the table.
 */
final public function remove_checked_rows(string $divid)
{ 
    $this->add('remove_checked_rows', array('divid' => $divid));
}

/**
 * Adds one or more rows to a table.  Used for a variety of reasons including 
 * search, sort, and pagination. 
 *
 * @param string $divid The element ID of the table.
 * @param string $table_alias The alias of the data table component, formatted PACKAGE:ALIAS
 * @param array $rows An array of associative arrays that contain the table row(s) to add.  Same as returned from $table->get_rows() method.
 * @param array $data Any additional data / attributes from within the <e:function> tag that called the table.
 */
final public function add_data_rows(string $divid, string $table_alias, array $rows, array $data = array())
{ 

    // Check table component
    if (!list($package, $parent, $alias) = components::check('table', $table_alias)) { 
        throw new ComponentException('not_exists_alias', 'table', $table_alias, 'not_exists');
    }

    // Load table
    $table = components::load('table', $alias, $package);

    // Check form field
    $form_field = $table->form_field ?? 'none';
    if ($form_field == 'checkbox' && !preg_match("/\[\]$/", $table->form_name)) { 
        $table->form_name .= '[]';
    }

    // Go through rows
    foreach ($rows as $row) { 

        // Add radio / checkbox, if needed
        $frow = array();
        if ($form_field == 'radio' || $form_field == 'checkbox') { 
            $frow[] = "<center><input type=\"$form_field\" name=\"" . $table->form_name . "\" value=\"" . $row[$table->form_value] . "\"></center>";
        }

        // Go through table columns
        foreach ($table->columns as $alias => $name) { 
            $value = $row[$alias] ?? '';
            $frow[] = $value;
        }

        // AJAX
        $this->add('add_data_row', array('divid' => $divid, 'cells' => $frow));
    }

}

/**
 * Sets the pagination links of a data table component. Used during 
 * pagination, search, and sort of a table. 
 *
 * @param string $divid The element ID of the table / pagination links
 * @param array $details Table details as provided by the 'apex\core\table_functions\get_details' method.
 */
final public function set_pagination(string $divid, array $details)
{ 

    // Get nav function
    $vars = app::getall_post();
    unset($vars['page']);
    $nav_func = "<a href=\"javascript:ajax_send('core/navigate_table', '" . http_build_query($vars) . "&page=~page~', 'none');\">";

    // Set AJAX
    $this->add('set_pagination', array(
        'divid' => $divid,
        'start' => $details['start'],
        'total' => $details['total'],
        'page' => $details['page'],
        'start_page' => $details['start_page'],
        'end_page' => $details['end_page'],
        'rows_per_page' => $details['rows_per_page'],
        'total_pages' => $details['total_pages'],
        'nav_func' => $nav_func)
    );

}

/**
 * ( Prepend text to an element. 
 *
 * @param string $divid The ID# of the DOM element to prepend to
 * @param string $html THe HTML to prepent.
 */
final public function prepend(string $divid, string $html)
{ 
    $this->add('prepend', array('divid' => $divid, 'html' => $html));
}

/**
 * Append text to an element 
 *
 * @param string $divid The ID# of the DOM element to append to
 * @param string $html THe HTML to append
 */
final public function append(string $divid, string $html)
{ 
    $this->add('append', array('divid' => $divid, 'html' => $html));
}

/**
 * Play found file 
 *
 * @param string $wav_file THe name of the WAV file found in the /public/plugins/sounds/ directory of Apex
 */
final public function play_sound(string $wav_file)
{ 
    $this->add('play_sound', array('sound_file' => $wav_file));
}

/**
 * Set text of a DOM element 9the innerHTML attribute) 
 *
 * @param string $divid The ID# of the DOM element to modify
 * @param string $text The new text of the DOM element (innerHTML attribute)
 */
final public function set_text(string $divid, $text)
{ 
    $this->add('set_text', array('divid' => $divid, 'text' => $text));
}

/**
 * Set display of a DOM element (general visible, none, block, etc.) 
 *
 * @param string $divid The ID# of the DOM element
 * @param string $display The display to set the DOM element to
 */
final public function set_display(string $divid, string $display)
{ 
    $this->add('set_display', array('divid' => $divid, 'display' => $display));
}

/**
 * Clear all items from a list 
 *
 * @param string $divid The ID# of the list to clear all items from
 */
public function clear_list(string $divid)
{ 
    $this->add('clear_list', array('divid' => $divid));
}


}

