<?php
declare(strict_types = 1);

namespace apex\core\ajax;

use apex\app;
use apex\app\web\ajax;

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

    // Set variables
    $package = app::_post('package') ?? '';

    // Load table
    $table = load_component('table', app::_post('table'), $package, '', app::getall_post());

    // Get table details
    $details = get_table_details($table, app::_post('id'));

    // Clear table
    $this->clear_table(app::_post('id'));

    // Add new rows
    $this->add_data_rows(app::_post('id'), app::_post('table'), $package, $details['rows'], app::getall_post());

    // Set pagination
    $this->set_pagination(app::_post('id'), $details);

}


}

