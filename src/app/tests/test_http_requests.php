<?php
declare(strict_types = 1);

namespace apex\app\tests;


use apex\app;

/**
 * Handles all remote HTTP requests that are sent via unit tests.  Any HTTP 
 * requests sent to the /unit_test/* URI of the system will pipe into this library, calling the method 
 * of the first segment of the URI.  For example, if sending to /unit_test/nexmo/..., the 
 * nexmo() method in this class will be called while unit tests are being performed.
 *
 * Things such as API URLs to connect to can be defined within the container definition files within the 
 * /bootstrap/ directory.  Simply put the actual API RUL within the http.php and cli.php bootstrap definitions, 
 * and a local URL for the test.php definition, which will call this class instead.
 *
 * For full information, please view the documentation for unit tests, and assigning variables within 
 * the dependency injection container at:
 *     https://apex-platform.org/docs/tests
 *      https://apex-platform.org/docs/di
 */
class test_http_requests
{


/**
 * Nexmo SMS test
 */
public function nexmo()
{

    // Set vars
    $vars = array(
        'status' => 'ok', 
        'phone' => app::_get('phone'), 
        'message' => app::_get('message'), 
        'from_name' => app::_get('from_name')
    );

    // Return
    return json_encode($vars);

}

}

