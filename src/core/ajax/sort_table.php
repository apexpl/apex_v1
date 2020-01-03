<?php
declare(strict_types = 1);

namespace apex\core\ajax;

use apex\app;
use apex\libc\components;
use apex\app\web\ajax;
use apex\app\utils\tables;
use apex\app\exceptions\ComponentException;

/**
 * Class that handles the AJAX based sorting of 
 * data tables.
 */
class sort_table extends ajax
{

/**
 * Sort table via AJAX 
 *
 * Sorts a table.  Removes all existing table rows, retrives the new rows from 
 * the 'table' component, and displays them in the browser. Used when clicking 
 * the up/down sort arrows in a data table column header. 
 */
public function process()
{ 


    // Check table component
    if (!list($package, $parent, $alias) = components::check('table', app::_post('table'))) { 
        throw new ComponentException('not_exists_alias', 'table', app::_post('table'));
    }

    // Load table
    $table = components::load('table', $alias, $package, '', app::getall_post());

    // Get table details
    $tbl_client = app::make(tables::class);
    $details = $tbl_client->get_details($table, app::_post('id'));

    // Clear table
    $this->clear_table(app::_post('id'));

    // Add new rows
    $this->add_data_rows(app::_post('id'), app::_post('table'), $details['rows'], app::getall_post());

    // Set pagination
    $this->set_pagination(app::_post('id'), $details);

}


}

