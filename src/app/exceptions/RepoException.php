<?php
declare(strict_types = 1);

namespace apex\app\exceptions;

use apex\app;
use apex\app\exceptions\ApexException;


/**
 * Handles all repository based errors, such as repo does not exists, unable 
 * to connect, invalid access, etc. 
 */
class RepoException   extends ApexException
{



    // Properties
    private $error_codes = array(
        'not_exists' => "No repository exists within the database with ID# {id}",
        'invalid_repo' => "No valid repository exists at the URL, {url}",
        'no_repos_exist' => "There are no repositories currently listed in the database.  Please add at least one repository (eg. php apex.php add_repo URL) before continuing.",
        'host_not_exists' => "No repository exists in this system with the host {url}",
        'remote_error' => "Repository returned error: {error}",

        // Repo API exceptions
        'not_enabled' => "This repository is not enabled, hence all API communication is disallowed.  If you are the site administrator, you may enable the repository via the Devel Kit-&gt;Settings menu of the administration panel",
        'no_api_method' => "The method {method} is not supported by this repository.  Please check the method name, and try again.",
        'no_upload' => "You did not upload a zip file to publish.",
        'no_auth' => "Invalid username or password.  Please check your login credentials, and try again.",
        'no_package_access' => "You do not have access to this package / theme.  If you believe this message is in error, please contact customer support for further details.",
        "package_not_exists" => "The requested package / theme does not exist on this repositroy.  Please check the alias, and try again.",
        'package_inactive' => "The requested package / theme does exist on this repository, but is not currently active.  Please try again later, or contact customer support for further information.",
        'zipfile_not_exists' => "This package exists within the database, but the zip file containg the package does not.  Please contact customer support for further information."
    );


/**
 * Construct 
 *
 * @param string $message The exception message
 * @param mixed $repo_id The ID# of the repo
 * @param string $url The repo URL trying to be accessed.
 * @param string $error_message The error message from the remote repo server.
 * @param string $method The repo method being performed.
 */
public function __construct($message, $repo_id = 0, $url = '', $error_message = '', $method = '')
{ 

    // Set variables
    $vars = array(
        'id' => $repo_id,
        'url' => $url,
        'error' => $error_message,
        'method' => $method
    );

    // Get message
    $this->log_level = 'error';
    $this->code = 500;
    $this->message = $this->error_codes[$message] ?? $message;
    $this->message = tr($this->message, $vars);

}


}

