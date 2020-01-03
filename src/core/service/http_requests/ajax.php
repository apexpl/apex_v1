<?php
declare(strict_types = 1);

namespace apex\core\service\http_requests;

use apex\app;
use apex\libc\auth;
use apex\libc\components;
use apex\app\exceptions\ApexException;
use apex\app\exceptions\ComponentException;


/**
 * Handles all AJAX based requests to the 
 * the system.  Call the appropriate 'ajax' components, 
 * and returns the results in JSON format.
 */
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
    if (count(app::get_uri_segments()) < 2) { 
        throw new ApexException('error', 'Invalid request.  No AJAX function defined.');
    }
    $ajax_alias = app::get_uri_segments()[0] . ':' . app::get_uri_segments()[1];

    // Check if component exists
    if (!list($package, $Parent, $alias) = components::check('ajax', $ajax_alias)) { 
        throw new ComponentException('not_exists_alias', 'ajax', $ajax_alias);
    }

    // Load the AJAX function class
    $client = components::load('ajax', $alias, $package);

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

