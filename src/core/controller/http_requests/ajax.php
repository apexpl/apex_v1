<?php
declare(strict_types = 1);

namespace apex\core\controller\http_requests;

use apex\app;
use apex\svc\auth;
use apex\svc\components;


class ajax
{




/**
 * Process AJAX request 
 *
 * Processes the AJAX request, handles all HTTP requests sent to /ajax/ URI 
 * Performs necessary back-end operations, then utilizes the /lib/ajax.php 
 * class to ( change DOM elements as necessary. 
 */
public function process()
{ 

    // Set response content-type to text/json,
    // so in case of error, a JSON error will be returned.
    app::set_res_content_type('application/json');

    // Ensure a proper alias and/or package is defined
    if (!isset(app::get_uri_segments()[0])) { trigger_error("Invalid request 123445", E_USER_ERROR); }
    if (isset(app::get_uri_segments()[1]) && app::get_uri_segments()[1] != '') { 
        $ajax_alias = app::get_uri_segments()[0] . ':' . app::get_uri_segments()[1];
    } else { 
        $ajax_alias = app::get_uri_segments()[0];
    }

    // Check if component exists
    if (!list($package, $Parent, $alias) = components::check('ajax', $ajax_alias)) { 
        trigger_error("AJAX function does not exist '$alias' within the package ''", E_USER_ERROR);
    }

    // Load the AJAX function class
    if (!$client = components::load('ajax', $alias, $package)) { 
        trigger_error("Unable to load the AJAX function '$alias' within the package '$package'", E_USER_ERROR);
    }

    // Check auth
    $auth_type = app::_post('auth_type') ?? 'user';
    if ($auth_type == 'admin') { app::set_area('admin'); }
    auth::check_login(false);

    // Process the AJAX function
    $response = $client->process();

    // Set response, if not autosuggest search
    if ($package != 'core' || $alias != 'search_autosuggest') { 
        $response = array(
            'status' => 'ok',
            'actions' => $client->results
        );
    }

    // Set the response
    app::set_res_body(json_encode($response));

}


}

