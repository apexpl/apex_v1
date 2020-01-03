<?php
declare(strict_types = 1);

namespace apex\core\ajax;

use apex\app;
use apex\libc\components;
use apex\app\utils\tables;
use apex\app\web\ajax;
use apex\app\exceptions\ComponentException;

/**
 * Class that handles the AJAX based search of 
 * data tables.
 */
class search_table extends ajax
{


/**
 * Search table via AJAX 
 *
 * Searches a table for given terms, removes all existing table rows, and 
 * replaces them with table rows that match the search.  This is the 'quick 
 * search' functionality of the data tables. 
 */
public function process()
{ 

    // Set variables
    $id = app::_post('id') ?? '';
    $search_text = app::_post('search_' . $id) ?? '';
    if ($search_text == '') { 
        $this->alert(tr('You did not specify any text to search for.'));
        return;
    }

    // Ensure table exists
    if (!list($package, $parent, $alias) = components::check('table', app::_post('table'))) { 
        throw new ComponentException('not_exists_alias', 'table', app::_post('table'));
    }

    // Load table
    $table = components::load('table', $alias, $package, '', app::getall_post());

    // Get attributes
    if (method_exists($table, 'get_attributes')) { 
        $table->get_attributes(app::getall_post());
    }

    // Get table details
    $utils = new tables();
    $details = $utils->get_details($table, $id);

    // Clear table rows
    $this->clear_table(app::_post('id'));

    // Add new rows
    $this->add_data_rows($id, app::_post('table'), $details['rows'], app::getall_post());

    // Set pagination
    $this->set_pagination(app::_post('id'), $details);

}


}

