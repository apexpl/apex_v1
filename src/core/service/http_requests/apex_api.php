<?php
declare(strict_types = 1);

namespace apex\core\service\http_requests;

use apex\app;
use apex\app\{db, redis, debug};
use apex\app\pkg\remote_access;
use apex\app\exceptions\ApexException;
use apex\core\service\http_requests;


/**
 * Remote API for the core Apex platform.
 */
class apex_api
{


/**
 * Handle Apex API request 
 *
 * Blank PHP class for the adapter.  For the correct methods and properties 
 * for this class, please review the abstract class located at: 
 * /src/core/adapter/core.php 
 */
public function process()
{ 

    // Set content type
    app::set_res_content_type('application/json');
    $action = app::get_uri_segments()[0] ?? '';

    // Check for method
    if (!method_exists($this, $action)) { 
        throw new ApexException('error', tr("Method does not exist, {1}", $action));
    }

    // Process method
    $response = $this->$action();

    // Set response
    app::set_res_body($response);

}

/**
 * Server check / get server health.
 */
protected function server_check()
{
    return app::_config('core:server_status');
}

/**
 * Remote copy files
 */
protected function remote_copy()
{

    // Process request
    $client = app::make(remote_access::class);
    $response = $client->process('copy');

    // Return
    return json_encode($response);

}

/**
 * Remote rm (delete files / directories).
 * 
 * @return string The JSON encoded response to return to client server.
 */
protected function remote_rm()
{

    // Process API call
    $client = app::make(remote_access::class);
    $response = $client->process('rm');

    // Return
    return json_encode($response);

}

/**
 * Remote save a component
 */
protected function remote_save()
{

    // Save component
    $client = app::make(remote_access::class);
    $response = $client->process('save');

    // Return
    return json_encode($response);

}

/**
 * Remotely delete a component
 *
 * @return The JSON encoded string to return to the client server.
 */
protected function remote_delete()
{

    // Delete component
    $client = app::make(remote_access::class);
    $response = $client->process('delete');

    // Return
    return json_encode($response);

}

/**
 * Execute SQL remotely.
 *
 * @param array $request The JSON decoded body contents of the request.
 *
 * @return string The JSON ended response to send to the client server.
 */
protected function remote_sql(array $request):string
{

    // Send request
    $client = app::make(remote_access::class);
    $response = $client->process('sql');

    // Return
    return json_encode($response);

}

/**
 * Remotely scan a package
 *
 * @param array $request The decoded JSON body conetents of the request.
 *
 * @param string The JSON encoded string to send back to client.
 */
protected function remote_scan(array $request):string
{

    // Send request
    $client = app::make(remote_access::class);
    $response = $client->scan($request['package']);

    // Return
    return json_encode($response);

}

}


