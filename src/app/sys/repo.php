<?php
declare(strict_types = 1);

namespace apex\app\sys;

use apex\app;
use apex\libc\{db, redis, io, encrypt, debug};
use apex\app\exceptions\RepoException;

/**
 * Class that handles all front facing repository 
 * management, such as adding / updating / deleting repos 
 * within the system configuration.
 */
class repo
{


/**
 * Add new repository
 *
 * @param string $host The hostname of the repo.
 * @param string $username Optional username of the repo account.
 * @param string $password Optional password for the repo account.
 * 
 * @return mixed The unique id# of the added repo, or false on failure.
 */
public function add(string $host, string $username = '', string $password = ''):int
{

    // Check if repo already exists, and update if yes
    if ($row = db::get_row("SELECT * FROM internal_repos WHERE host = %s", $host)) { 
        $this->update((int) $row['id'], $username, $password);
        return (int) $row['id'];
    }

    // Validate via SSL
    $is_ssl=1;
    if (!$vars = $this->validate($host, true)) { 
        if (!$vars = $this->validate($host, false)) { 
            throw new RepoException('invalid_repo', 0, $host);
        }
        $is_ssl = 0;
    }

    // Add to database
    db::insert('internal_repos', array(
        'is_ssl' => $is_ssl,
        'host' => strtolower($host), 
        'username' => encrypt::encrypt_basic($username),
        'password' => encrypt::encrypt_basic($password),
        'name' => $vars['repo_name'],
        'description' => $vars['repo_description'])
    );
    $repo_id = db::insert_id();

    // Return
    return (int) $repo_id;

}

/**
 * Update repo
 *
 * @param int $repo_id The id# of the repo to update.
 * @param string $username Username to update repo to.
 * @param string $password Password to update repo to.
 *
 * @return bool WHether or not the operation was successful.
 */
public function update(int $repo_id, string $username, string $password)
{

    // Update database
    db::update('internal_repos', array(
        'username' => encrypt::encrypt_basic($username),
        'password' => encrypt::encrypt_basic($password)), 
    "id = %i", $repo_id);

    // Return
    return true;

}

/**
 * Check whether or not a repository is valid. 
 *
 * @param string $host The hostname to check
 & @param bool $is_ssl Whether or not to try and validate via SSH, defaults to true.  If false, validates via port 80.
 *
 * @return Mixed Array of info on the repository if successful, and false otherwise.
 */
public function validate(string $host, bool $is_ssl = true)
{ 

    // Get URL
    $url = $is_ssl === true ? 'https://' : 'http://';
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

}

