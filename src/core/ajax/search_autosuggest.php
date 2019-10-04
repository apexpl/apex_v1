<?php
declare(strict_types = 1);

namespace apex\core\ajax;

use apex\app;
use apex\svc\db;
use apex\app\web\ajax;
use apex\svc\components;

class search_autosuggest Extends ajax
{

/**
 * Loads the appropriate autosuggest component, performs a 
 * search, and displays the results to the browser.
 */
public function process() 
{

    // Set variables
    list($package, $parent, $alias) = components::check('autosuggest', app::_get('autosuggest'));

    // Load autosuggest
    $autosuggest = components::load('autosuggest', $alias, $package, '', app::getall_get());

    // Get options
    $options = $autosuggest->search(app::_get('term'));

    // Format options
    $results = array();
    foreach ($options as $id => $label) { 
        array_push($results, array('label' => $label, 'data' => $id));
    }

    // return
    return $results;


}

}

