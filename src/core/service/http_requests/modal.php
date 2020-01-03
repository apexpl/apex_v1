<?php
declare(strict_types = 1);

namespace apex\core\service\http_requests;

use apex\app;
use apex\libc\view;
use apex\libc\components;
use apex\app\exceptions\ApexException;
use apex\app\exceptions\ComponentException;
use apex\core\service\http_requests;

/**
 * Handles viewing of all modals (pop up boxes) 
 * within the system.
 */
class modal extends http_requests
{


/**
 * Display a modal 
 *
 * Processes all modals that are opened via the open_modal() Javascript 
 * function. 
 */
public function process()
{ 

    // Set response content-type to text/json, so
    // in case of error, a JSON response is returned.
    app::set_res_content_type('application/json');

    // Ensure a proper modal was defined in URI
    if (!isset(app::get_uri_segments()[0])) { 
        throw new ApexException('error', "Invalid request.  No modal defined.");
    }

    // Get package / alias
    if (!list($package, $parent, $alias) = components::check('modal', app::get_uri_segments()[0])) { 
        throw new ComponentException('not_exists', 'modal', app::get_uri_segments()[0]);
    }

    // Get TPL code
    $tpl_file = SITE_PATH . '/' . components::get_tpl_file('modal', $alias, $package);
    if (!file_exists($tpl_file)) { 
        throw new ApexException('error', "The TPL file does not exist for the modal, $parts[0]", E_USER_ERROR); 
    }
    $tpl_code = file_get_contents($tpl_file);

    // Get title
if (preg_match("/<h1>(.+?)<\/h1>/i", $tpl_code, $match)) { 
        $title = $match[1];
        $tpl_code = str_replace($match[0], "", $tpl_code);
    } else { $title = tr('Dialog'); }

    // Load component
    if (!$client = components::load('modal', $alias, $package)) { 
        throw new ComponentException('no_load', 'modal', '', $alias, $package);
    }

    // Execute show() method, if exists
    if (method_exists($client, 'show')) { 
        components::call('show', 'modal', $alias, $package); 
    }

    // Parse HTML
    view::initialize();
    view::load_base_variables();
    $html = view::parse_html($tpl_code);

    // Set results array
    $results = array(
        'title' => view::parse_html($title),
        'body' => $html
    );

    // Set response
    app::set_res_body(json_encode($results));
}


}

