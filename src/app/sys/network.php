<?php
declare(strict_types = 1);

namespace apex\app\sys;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\svc\io;
use apex\svc\components;
use apex\svc\encrypt;
use apex\app\exceptions\RepoException;
use ZipArchive;
use CurlFile;


/**
 * Handles various general repository communication such as retrieving a list 
 * of packages and themes, checking for available upgrades, and so on. 
 */
class Network
{



/**
 * Check whether or not a repository is valid. 
 *
 * @param string $host The hostname to check
 * @param int $is_ssl A 1/0, whether or not to check via SSL
 *
 * @return bool WHether or not ti's a valid repo.
 */
public function check_valid_repo(string $host, int $is_ssl = 1)
{ 

    // Get URL
    $url = $is_ssl == 1 ? 'https://' : 'http://';
    $url .= $host . '/repo_api/get_info';

    // Send request
    if (!$response = io::send_http_request($url)) { 
        return false;
    } elseif (!$vars = json_decode($response, true)) { 
        return false;
    }

    // Check for valid repo
    $is_repo = $vars['is_apex_repo'] ?? 0;
    return ($is_repo == 1 ? $vars : false);

}

/**
 * List all available packages on a repo 
 *
 * @param string $type The type to list -- 'package' or 'theme'.
 *
 * @return array An array of arrays, with each element being an array containing details on one page that is available.
 */
public function list_packages(string $type = 'package')
{ 

    // Debug
    debug::add(5, "Starting to list_packages from all repos");

    // Go through all repos
    $packages = array(); $done = array();
    $rows = db::query("SELECT * FROM internal_repos WHERE is_active = 1 ORDER BY id");
    foreach ($rows as $row) { 

        // Debug
        debug::add(5, tr("Sending list_packages request to repo ID# {1}, host: {2}", $row['id'], $row['host']));

        // Send repo request
        iF (!$response = $this->send_repo_request((int) $row['id'], '', 'list', array('type' => $type), true)) { 
            continue;
        }
        if (!isset($response['packages'])) { continue; }

        // Go through packages
        foreach ($response['packages'] as $alias => $vars) { 
            if (in_array($alias, $done)) { continue; }

            $vars['name'] .= ' v' . $vars['version'];
            $vars['date_created'] = fdate($vars['date_created']);
            $vars['last_modified'] = $vars['last_modified'] === null ? 'N/A' : fdate($vars['last_modified'], true);
            $vars['alias'] = $alias;

            array_push($packages, $vars);
            $done[] = $alias;
        }

        // Debug
        debug::add(5, tr("Finished getting list_packages from repo ID# {1}, host: {2}", $row['id'], $row['host']));
    }

    // Debug
    debug::add(3, "Finished executing list_packages on all repos, returning results");

    // Return
    return $packages;

}

/**
 * Checks to see whether or not a package alias exists in any of the 
 * repositories configured on this system. 
 *
 * @param string $pkg_alias The package alias to search for.
 * @param string $type The type -- 'package' or 'theme'
 *
 * @return array An array of all repos that contain the package.
 */
public function check_package(string $pkg_alias, string $type = 'package'):array
{ 

    // Debug
    debug::add(5, tr("Starting check_package of all repos for package alias: {1}", $pkg_alias));

    // Go through repos
    $repos = array();
    $rows = db::query("SELECT * FROM internal_repos WHERE is_active = 1 ORDER BY id");
    foreach ($rows as $row) { 

        // Debug
        debug::add(5, tr("Sending check_package repo request for package alias: {1}, to repo ID# {2}, host: {3}", $pkg_alias, $row['id'], $row['host']));

        // Send request
        if (!$vars = $this->send_repo_request((int) $row['id'], $pkg_alias, 'check', array('type' => $type), true)) { 
            continue;
        }

        // Check
        $ok = $vars['exists'] ?? 0;
        debug::add(5, tr("Received check_package response from repo of {1} for package alias {2} from repo ID# {3}, host: {4}", $ok, $pkg_alias, $row['id'], $row['host']));

        // Continue, if not exists
        if ($ok != 1) { continue; }

        // Add to results
        $name = $vars['repo_name'] . '(' . $vars['repo_host'] . ')';
        $repos[$row['id']] = $name;
    }

    // Debug
    debug::add(3, "Finished sending check_package repo request to all repos");

    // Return
    return $repos;

}

/**
 * Search all repositories 
 *
 * Searches all repos configured on this system for a given term, for any 
 * packages that match.  Unlike the 'check_package' method, this does not have 
 * to be the exact alias, and will search the alias, name and description of 
 * all packages for given term. 
 *
 * @param string $term The term to search for.
 *
 * @return string A message containg all packages and repos that match the term.
 */
public function search(string $term):string
{ 

    // Debug
    debug::add(5, tr("Starting to search packages on all repos for term: {1}", $term));

    // Go through repos
    $results = '';
    $rows = db::query("SELECT * FROM internal_repos WHERE is_active = 1 ORDER BY id");
    foreach ($rows as $row) { 

        // Set request
        $request = array(
            'term' => $term
        );

        // Debug
        debug::add(5, tr("Searching packages for term: {1} on repo ID# {2}, host: {3}", $term, $row['id'], $row['host']));

        // Send request
        if (!$vars = $this->send_repo_request((int) $row['id'], '', 'search', $request, true)) { 
            continue;
        }
        if (count($vars['packages']) == 0) { continue; }

        // Add to results
        $results .= "Repo: " . $row['name'] . " (" . $row['host'] . ")\n\n";
        foreach ($vars['packages'] as $alias => $name) { 
            $results .= "\t$alias -- $name\n";
        }

    }

    // Check if no results
    if ($results == '') { 
        $results = "No packages found matching that term.\n\n";
    }

    // Debug
    debug::add(3, tr("Finished search packages on all repos for term: {1}", $term));

    // Return
    return $results;

}

/**
 * Check for upgrades 
 *
 * @param array $packages An array of packages to check for upgrades.  If none specified, all packages installed in the system will be checked.
 */
public function check_upgrades(array $packages = array())
{ 

    // Get all packages, if needed
    if (count($packages) == 0) { 
        $packages = db::get_column("SELECT alias FROM internal_packages");
    }

    // Go through packages
    $requests = array();
    foreach ($packages as $pkg_alias) { 

        // Get row
        if (!$row = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", $pkg_alias)) { 
            continue;
        }

        // Add to requests
        $repo_id = $row['repo_id'];
        if (!isset($requests[$repo_id])) { $requests[$repo_id] = array(); }
        $requests[$repo_id]['pkg_' . $pkg_alias] = $row['version'];
    }

// Go through requests
    $upgrades = array();
    foreach ($requests as $repo_id => $request) { 

// Ensure repo exists
        if (!$repo = db::get_idrow('internal_repos', $repo_id)) { 
            continue;
        }

        // Send request
        if (!$vars = $this->send_repo_request((int) $repo_id, '', 'check_upgrades', $request, true)) { 
            continue;
        }
        if (!isset($vars['upgrades'])) { continue; }
        $upgrades = array_merge($upgrades, $vars['upgrades']);
    }

    // Return
    return $upgrades;

}

/**
 * Send HTTP request to repository 
 *
 * @param int $repo_id The ID# of the repository to send the request to
 * @param string $alias The alias of the package /theme the request is for
 * @param string $action The action being performed
 * @param array $request The contents of the POST request
 * @param bool $noerror If true, will return false instead of triggering an error
 * @param string $zip_file Optional zip file to upload with the package / theme / upgrade.
 *
 * @return mixed Array of the JSON response, or false if failed
 */
public function send_repo_request(int $repo_id, string $alias, string $action, array $request = array(), bool $noerror = false, string $zip_file = '')
{ 

    // Get repo
    if (!$repo = db::get_idrow('internal_repos', $repo_id)) { 
        if ($noerror === true) { return false; }
        throw new RepoException('not_exists', $repo_id);
    }

    //  Set request
    $request['alias'] = $alias;
    if ($repo['username'] != '') { $request['username'] = encrypt::decrypt_basic($repo['username']); }
    if ($repo['password'] != '') { $request['password'] = encrypt::decrypt_basic($repo['password']); }

    // Get URL
    $url = $repo['is_ssl'] == 1 ? 'https://' : 'http://';
    $url .= $repo['host'] . '/repo_api/' . $action;

    // Send HTTP request
    if (!$response = io::send_http_request($url, 'POST', $request)) { 
        return false;

    // Decode response
    } elseif (!$vars = json_decode($response, true)) { 
        return false;
    }

    // Check response status
    if ($vars['status'] != 'ok') { 
        if ($noerror === true) { return false; }
        throw new RepoException('remote_error', 0, '', $vars['errmsg']);
    }

    // Upload cheunked file, if needed
    if ($zip_file != '') { 

        // Get repo host
        $host = $repo['is_ssl'] == 1 ? 'https://' : 'http://';
        $host .= $repo['host'];

        // Get local file
        $local_file = sys_get_temp_dir() . '/tmp.zip';
        if (file_exists($local_file)) { @unlink($local_file); }
        rename(sys_get_temp_dir() . '/' . $zip_file, $local_file);

        // Upload file
        io::send_chunked_file($host, $local_file, $zip_file);
        @unlink($local_file);
    }

    // Return
    return $vars;

}


}

