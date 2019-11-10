<?php
declare(strict_types = 1);

namespace apex\core\ajax;

use apex\app;
use apex\app\web\ajax;


class navigate_table extends ajax
{




/**
 * Navigates to a different page within a data table via AJAX. ( Used when one 
 * of the pagination links are clicked. 
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

    // Add data rows
    $this->add_data_rows(app::_post('id'), app::_post('table'), $package, $details['rows'], app::getall_post());

    // Set pagination
    if ($details['has_pages'] === true) { $this->set_pagination(app::_post('id'), $details); }

}


}

