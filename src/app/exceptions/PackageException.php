<?php
declare(strict_types = 1);

namespace apex\app\exceptions;

use apex\app;
use apex\app\exceptions\ApexException;


/**
 * Handles all package related errors, such as package does not exists, 
 * package already exists, unable to load / publish / download package, etc. 
 */
class PackageException   extends ApexException
{



    // Properties
    private $error_codes = array(
        'undefined' => "You did not specify a package alias, and one is required for this action",
        'not_exists' => "The package does not exist with alias, {alias}",
        'exists' => "The package already exists in this system with alias, {alias}",
        'invalid_alias' => "An invalid package alias was specified, {alias}",
        'no_open_upgrades' => "There are no open upgrades on the package {alias} to publish.  You must first create an upgrade point with: php apex.php create_upgrade PACKAGE",
        'config_not_exists' => "The package.php configuration file does not exist for the package, {alias}",
        'config_no_load' => "Unable to load package configuration file for the package, {alias}", 
        'git_already_init' => "A local Github repository has already been initialized for the package, {alias}.  If you need to re-initialize the repo, delete the directory at /src/{alias}/git", 
        'git_undefined_repo_url' => "No Github repo URL defined.  Please first define a repo URL within the properties of the /etc/{alias]/package.php file, and try again.", 
        'git_no_remote_archive' => 'No remote archive file found within git repository for the package {alias}'
    );
/**
 * Construct 
 * 
 * @param string $message The exceptio message.
 * @param string $pkg_alias The package alias being affected.
 */
public function __construct(string $message, $pkg_alias = '')
{ 

    // Set variables
    $vars = array(
        'alias' => $pkg_alias
    );

    // Get message
    $this->log_level = 'error';
    $this->code = 500;
    $this->message = $this->error_codes[$message] ?? $message;
    $this->message = tr($this->message, $vars);

}


}

