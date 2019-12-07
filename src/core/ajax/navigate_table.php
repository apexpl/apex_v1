<?php
declare(strict_types = 1);

namespace apex\core\ajax;

use apex\app;
use apex\svc\components;
use apex\app\web\ajax;
use apex\app\utils\tables;
use apex\app\exceptions\ComponentException;


class navigate_table extends ajax
{




/**
 * Navigates to a different page within a data table via AJAX. ( Used when one 
 * of the pagination links are clicked. 
 */
public function process()
{ 

    // Set variables
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

    // Add data rows
    $this->add_data_rows(app::_post('id'), app::_post('table'), $details['rows'], app::getall_post());

    // Set pagination
    if ($details['has_pages'] === true) { 
        $this->set_pagination(app::_post('id'), $details); 
    }

}


}

