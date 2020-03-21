<?php
declare(strict_types = 1);

namespace apex\app\pkg;

use apex\app;
use apex\libc\{db, redis, debug, components, io};
use apex\app\exceptions\{ApexException, ComponentException};

/**
 * Class that handles the remote access client, and sends 
 * requests for updates to remote servers.
 */
class remote_access_client
{

    // Properties
    private string $host;
    private string $api_key;

/**
 * Construct
 */
public function __construct(string $host = '', string $api_key = '')
{

    // Set variables
    $this->host = $host == '' ? app::_config('core:remote_api_host') : $host;
    $this->api_key = $api_key == '' ? app::_config('core:remote_api_key') : $api_key;

}

/**
 * Copy a file(s)
 *
 * @param array $files List of files to copy, relative to the installation directory.
 *
 * @return array The JSON decoded response from remote server.
 */
public function copy(array $filenames)
{

    // Check files, and create request
    $files = [];
    foreach ($filenames as $file) { 

        // Check file
        if (!file_exists(SITE_PATH . '/' . $file)) { 
            throw new ApexException('error', tr("Unable to copy file, as it does not exist, {1}", $file));
        }

        // Add to request
        $files[$file] = base64_encode(file_get_contents(SITE_PATH . '/' . $file));
    }

    // Set request
    $request = [
        'files' => $files
    ];

    // Send request
    $response = $this->send_request('remote_copy', $request);

    // Return
    return $response;

}

/**
 * Remote a file / directory
 *
 * @param array $files The files / directories to delete off the remote server.
 *
 * @return array The JSON decoded response from remote server.
 */
public function rm(array $files)
{

    // Set request
    $request = [
        'files' => $files
    ];

    // Send request
    $response = $this->send_request('remote_rm', $request);

    // Return
    return $response;

}

/**
 * Save a component
 * 
 * @param string $type The type of component to save.
 * @param string $comp_alias The alias of the component in Apex format (ie. PACKAGE:[PARENT:]ALIAS).
 *
 * @return array Five elements, the status (ok / pending), type, comp_alias, and array of files.
 */
public function save(string $type, string $comp_alias)
{

    // Perform checks
    if (!in_array($type, COMPONENT_TYPES)) { 
        throw new APexException('error', tr("Invalid component type, {1}", $type));
    } elseif ($comp_alias == '') { 
        throw new ApexException('error', "You did not specify a component alias");
    } elseif (!list($alias, $package, $parent) = components::check($type, $comp_alias)) { 
        throw new ComponentException('not_exists_alias', $type, $comp_alias);
    }

    // Start request
    $request = [
        'type' => $type, 
        'comp_alias' => $comp_alias, 
        'files' => []
    ];

    // Get owner
    if (!$request['owner'] = db::get_field("SELECT owner FROM internal_components WHERE type = %s AND package = %s AND alias = %s AND parent = %s", $type, $package, $alias, $parent)) { 
        throw new ApexException('error', "Unable to determine owner for components type {1}, comp_alias {2}", $type, $comp_alias);
    }

    // Go through files
    $files = components::get_all_files($type, $alias, $package, $parent);
    foreach ($files as $file) { 
        $request['files'][$file] = base64_encode(file_get_contents(SITE_PATH . '/' . $file));
    }

    // Send request
    $response = $this->send_request('remote_save', $request);

    // Return
    return $response;

}

/**
 * Delete a component
 *
 * @param string $type The type of component
 * @param string $comp_alias The component alias in Apex format (ie. PACKAGE:[PARENT:}ALIAS).
 *
 * @return array The JSON decoded response from the remote server.
 */
protected function delete(string $type, string $comp_alias):array
{

    // Set request
    $request = [
        'type' => $type, 
        'comp_alias' => $comp_alias
    ];

    // Send request
    $response = $this->send_request('remote_delete', $request);

    // Return
    return $response;

}

/**
 * Execute a SQL statement
 *
 * @param string $sql The SQL statement to run
 *
 * @return array The response to send to the client server via JSON.
 */
public function sql(string $sql)
{

    // Set request
    $request = [
        'sql' => $sql
    ];

    // Send request
    $response = $this->send_request('remote_sql', $request);

    // Return
    return $response;

}

/**
 * Scan a package
 *
 * @param string $pkg_alias The package alias to scan.
 *
 * @return array The response to send back to the client server.
 */
public function scan(string $pkg_alias):array
{

    // Set request
    $request = [
        'package' => $pkg_alias, 
        'config_file' => base64_encode(file_get_contents(SITE_PATH . '/etc/' . $pkg_alias . '/package.php'))
    ];

    // Send request
    $response = $this->send_request('remote_scan', $request);

    // Return
    return $response;

}

/**
 * Send request
 *
 * @param string $action The action to perform.
 * @param array $request The request to send.
 *
 * @return array The JSON decoded response from the server.
 */
protected function send_request(string $action, array $request)
{

    // Set request
    $headers = ['Apex-API-Key: ' . $this->api_key];
    $url = trim($this->host, '/') . '/apex_api/' . $action;

    // Send http request
    if (!$response = io::send_http_request($url, 'POST', json_encode($request), 'application/json', 0, $headers)) { 
        throw new ApexException('error', tr("Did not receive a valid response from the remote server, {1}", $url));
    } elseif (!$vars = json_decode($response, true)) { 
        throw new ApexException('error', tr("Did not receive a valid response from the remote server, {1}.  Full server response is:  {2}", $url, $response));
    }
    if (!isset($vars['status'])) { $vars['status'] = 'unknown'; }

    // Return
    return $vars;

}


}


