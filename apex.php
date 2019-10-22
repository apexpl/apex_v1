<?php
declare(strict_types = 1);

namespace apex;

use apex\app;
use apex\svc\components;
use apex\app\sys\apex_cli;

/**
 * Check the cwd and ensure we're currently inside an Apex installation 
 * directory in case we're executing a phar archive from the environment path.
 */
$cwd = check_cwd();

/**
 * Load up composer, so we have access to all of our goodies. 
 */
require_once($cwd . '/vendor/autoload.php');

/**
 * Create our application, and get ready to handle the request.
 */
$app = new app('cli');

/**
 * Ensure we're executing via CLI, get the method and arguments to perform.
 */
if (php_sapi_name() != "cli") {
    die("You can only execute this script via CLI.  Please login to your server via SSH, and run commands from prompt.");
} elseif (!isset($argv[1])) { 
    die("No command specified.  Please use the 'help' option for a list of available commands.");
}

// Get method and arguments
$method = strtolower($argv[1]);
array_splice($argv, 0, 2);


/**
 * Handle the CLI request, execute via the apex_cli.php class if available, 
 * and otherwise check for CLI command created by another package.
 */

// Execute internal CLI command, if needed
$client = $app->make(apex_cli::class);
if (in_array($method, $client->methods)) { 
    $response = $app->call([apex_cli::class, $method], ['vars' => $argv]);

// Check for package CLI command
} elseif (preg_match("/^(\w+?)\.(\w+)$/", $method, $match)) { 

    // Check if component exists
    if (!list($package, $parent, $alias) = components::check('cli', $match[1] . ':' . $match[2])) { 
        die("No CLI command exists at $method.  Please use the 'help' command for a list of available commands.");
    }

    // Call process
    $class_name = "apex\\" . $package . "\\cli\\" . $alias;
    $response = $app->call([$class_name, 'process'], ['args' => $argv]);

// No command exists
} else { 
    die("No CLI command exists at $method.  Please use the 'help' command for a list of available commands.");
}

// Echo response, and exit
echo $response;
exit(0);


/**
 * Check the CWD
 *
 * Get the current cwd, checks to ensure its a correct Apex installation.  Used 
 * when the 'apex' phar archive is located within the environment path.
 */
function check_cwd()
{

    // Check
    $dir = getcwd();
    if (!file_exists("$dir/etc/config.php")) { die("Not in an Apex installation directory."); }
    if (!file_exists("$dir/src/app.php")) { die("Not in an Apex installation directory."); }
    if (!file_exists("$dir/vendor/autoload.php")) { die("Composer packages have not yet been installed.  Please first install with:  composer update"); }

    // Return
    return $dir;

}


