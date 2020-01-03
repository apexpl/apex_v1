<?php
declare(strict_types = 1);

namespace apex\core\service\http_requests;

use apex\app;
use apex\libc\db;
use apex\libc\debug;
use apex\app\tests\test_http_requests;
use apex\core\service\http_requests;
use apex\app\exceptions\ApexException;

/**
 * HTTP adapter
 */
class unit_test extends http_requests
{

/**
 * Process the HTTP request.
 *
 * Middleware class to handle the HTTP request for any URIs that goto /unit_test/*.  Use the 
 * app::get_uri_segments() and app::get_uri() methods to determine the exact URI being 
 * requested, and process accordingly.  Use the following methods to 
 * set a response for the request.
 *    app::set_res_contents()
 *    app::set_res_http_status()
 *    app::set_res_content_type()
 */
public function process()
{

    // Initialize
    $client = app::make(test_http_requests::class);

    // Check method
    $method = app::get_uri_segments()[0] ?? '';
    if (!method_exists($client, $method)) { 
        throw new ApexException('error', tr("No method exists within http test  library at {1}", $method));
    }

    // Execute
    $response = $client->$method();

    // Set body
    app::set_Res_body($response);

}

}




