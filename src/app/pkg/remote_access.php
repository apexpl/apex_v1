<?php
declare(strict_types = 1);

namespace apex\app\pkg;

use apex\app;
use apex\libc\{db, redis, debug, io};
use apex\app\pkg\pkg_component;
use apex\app\exceptions\ApexException;


/**
 * Class that handles all remote access calls via the API, allowing 
 * developers / designers to easily upload modified files and components 
 * via their local Apex install.
 */
class remote_access
{


/**
 * Process an API call.
 * 
 * @param string $action The action of the API call to execute.
 *
 * @return array The response to be encoded via JSON
 */
public function process(string $action):array
{

    // Check authentication
    $this->check_auth();

    // Ensure method exists
    if (!method_exists($this, $action)) { 
        throw new ApexException('error', tr("Remote access internal API method does not exist, {1}", $action));
    }

    // Decode request
    $request = json_decode(app::get_request_body(), true);

    // Execute method
    $response = $this->$action($request);

    // Return
    return $response;

}

/**
 * Copy files over.
 *
 * @param string $filename The name of the file to save, relative to the installation directory.
 * @param array $request The decoded JSON object from the body contents.
 *
 * @return array The response, including status and array of files copied.
 */
protected function copy(array $request):array
{

    // Initialize
    $status = 'ok';
    $files = $request['files'] ?? [];

    // Go through files
    foreach ($files as $filename => $contents) { 

        // Check if writeable
        $file = SITE_PATH . '/' . trim($filename, '/');
        if (!is_writable(SITE_PATH . '/src/app.php')) { 
            redis::hset('remote_update', $filename, $contents);
            $status = 'pending';
            continue;
        }

        // Save the file
        io::create_dir(dirname($file));
        file_put_contents($file, base64_decode($contents));
    }

    // Set request
    $response = [
        'status' => $status, 
        'files' => array_keys($files)
    ];

    // Return
    return $response;

}

/**
 * Delete files / directories
 *
 * @param array $request The decoded JSON body of the request.
 *
 * @return array The response including status and an array of files that were deleted.
 */
protected function rm(array $request):array
{

    // Initialize
    $status = 'ok';
    $files = $request['files'] ?? [];

    // Go through files
    foreach ($files as $filename) { 

        // Check file exists
        $file = SITE_PATH . '/' . trim($filename, '/');
        if (file_exists($file)) { 

            if (!is_writeable($file)) { 
                redis::lpush('remote_rm', $filename);
                $status = 'pending';
            } else { 
                @unlink($file);
            }

        // Check directory
        } elseif (is_dir($file)) { 

            // Check if writeable
            if (!is_writeable($file)) { 
                redis::lpush('remote_rm', $filename);
                $status = 'pending';
            } else { 
                io::remove_dir($file);
            }
        }
    }

    // Set response
    $response = [
        'status' => $status, 
        'files' => $files
    ];

    // Return
    return $response;

}

/**
 * Check auth.
 *
 * Checks to ensure the appropriate API key was posted to the 
 * server with the request.
 */
private function check_auth()
{

    // Check API key
    if (!$header = app::_header('apex-api-key')) { 
        app::set_res_http_status(403);
        throw new ApexException('error', 'Invalid request.  You do not have authorization');
    } elseif (hash('sha256', $header[0]) != app::_config('core:update_api_key')) { 
        app::set_res_http_status(403);
        throw new ApexException('error', 'Invalid request.  You do not have authorization');
    }

}

/**
 * Save a component
 *
 * @param array $request The decoded JSON body contents of the request.
 *
 * @param array The response including status, type, comp_alias and an array of files.
 */
public function save(array $request):array
{

    // Initialize
    $files = $request['files'] ?? [];

    // Check for writeable
    if (!is_writeable(SITE_PATH . '/src/app.php')) { 
        redis::lpush('remote_save', json_encode($request));
        $request['status'] = 'pending';
        return $request;
    }

    // Save component
    pkg_component::add($request['type'], $request['comp_alias'], '', 0, $request['owner']);

    // Go through files
    foreach ($files as $filename => $contents) { 
        $file = SITE_PATH . '/' . trim($filename, '/');
        file_put_contents($file, base64_decode($contents));
    }

    // Set response
    $response = [
        'status' => $status, 
        'type' => $request['type'], 
        'comp_alias' => $request['comp_alias'], 
        'owner' => $request['owner'], 
        'files' => $files
    ];

    // Return
    return $response;

}

/**
 * Delete a component
 *
 * @param array $request The JSON decoded body contents of the request.
 *
 * @return array The response, including status, type, comp_alias and files deleted.
 */
public function delete(array $request):array
{

    // Check if writeable
    if (!is_writeable(SITE_PATH . '/src/app.php')) { 
        redis::lpush('remote_delete', json_encode($request));
        $request['status'] = 'pending';
        return $request;
    }

    // Delete component
    pkg_component::remove($request['type'], $request['comp_alias']);

    // Set response
    $response = [
        'status' => 'ok', 
        'type' => $request['type'], 
        'comp_alias' => $request['comp_alias'], 
    ];

    // Return
    return $response;

}

/**
 * Execute SQL statement
 *
 * @param array $request The decoded JSON body contents of the request.
 *
 * @param array The response to provide to the client server.
 */
protected function sql(array $request):array
{

    // Execute SQL
    $result = db::query($request['sql']);

    // Get rows
    $rows = [];
    if (is_iterable($result)) { 

        $rows = [];
        while ($row = db::fetch_assoc($result)) { 
            $rows[] = $row;
        }
    }

    // Set response
    $response = [
        'status' => 'ok', 
        'sql' => $request['sql'], 
        'rows' => $rows
    ];

    // Return
    return $response;

}

/**
 * Scan a page
 *
 * @param array $request The JSON decoded body contents of the request
 *
 * @return array The response to send back to the client server.
 */
public function scan(array $request):array
{

    // Initialize
    $config_file = SITE_PATH . '/etc/' . $request['package'] . '/package.php';

    // Check for writeable
    if (!is_writeable($config_file)) { 
        redis::lpush('remote_scan', json_encode($request));
        $request['status'] = 'pending';
        return $request;
    }

    // Save config file
    file_put_contents($config_file, base64_decode($request['config_file']));

    // Scan package
    $client = new package_config($request['package']);
    $client->install_configuration();

    // Set response
    $response = [
        'status' => 'ok'
    ];

    // Return
    return $response;

}

}


