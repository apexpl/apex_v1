<?php
declare(strict_types = 1);

namespace apex\app\sys;

use apex\app;
use apex\libc\{db, redis, encrypt, io, components, debug};
use apex\app\pkg\{package_config, package, upgrade, theme, pkg_component, github, crud};
use apex\app\sys\{network, repo};
use apex\app\exceptions\{ApexException, PackageException, ComponentException, UpgradeException, ThemeException, RepoException};


/**
 * Class that handles all functions executed via the apex.php CLI script 
 * within the installation directory of Apex. 
 */
class apex_cli
{

    /**
     * @Inject
     * @var app
     */
    private $app;

    // Properties
    public $methods;


/**
 * Constructor.  Grab some injected dependencies as needed. 
 */
public function __construct()
{ 

    $this->methods = get_class_methods($this);

}

/**
 * Display help 
 *
 * Usage:  php apex.php help 
 *
 * @param iterable $vars The command line arguments specified by the user.
 */
public function help($vars)
{ 

    // General commands
    $response = "\n\tGENERAL\n\n";
    $response .= str_pad("search TERM", 40) . "Searches all configured repos for packages matching TERM\n";
    $response .= str_pad("list_packages", 40) . "Lists all packages available to the system from all configured repos\n";
    $response .= str_pad('list_themes', 40) . "Lists all themes available to the system\n";
    $response .= str_pad('install PACKAGE', 40) . "Downloads and installs the specified package\n";
    $response .= str_pad('install_theme THEME', 40) . "Downloads and installs the specified theme\n";
    $response .= str_pad('upgrade [PACKAGE]', 40) . "Downloads and installs all upgrades available.\n";
    $response .= str_pad('change_theme AREA THEME', 40) . "Changes active theme on AREA (public / members) to the specified theme\n\n";

    // Package commands
    $response .= "\tPACKAGES\n";
    $response .= str_pad('create_package PACKAGE', 40) . "Creates a new package for development\n";
    $response .= str_pad('scan PACKAGE', 40) . "Scans configuration file of package, and updates database as needed\n";
    $response .= str_pad('publish PACKAGE', 40) . "Publishes package to repository\n";
    $response .= str_pad('delete_package PACKAGE', 40) . "Deletes specified package from the system\n\n";

    // Upgrade
    $response .= "\tUPGRADES\n";
    $response .= str_pad('create_upgrade PACKAGE [VERSION]', 40) . "Create new upgrade point on specified package.  optionally, specify version of upgrade\n\n";
    $response .= str_pad('publish_upgrade PACKAGE', 40) . "Publish open upgrade on specified package\n";
    $response .= str_pad('check_upgrades', 40) . "Lists all upgrades available to the system\n";
    $response .= str_pad('upgrade [PACKAGE]', 40) . "Downloads and installd all upgrades available.\n";
    $response .= str_pad('rollback PACKAGE [VERSION]    ', 40) . "Reverts package to the specified version.\n";


    // Theme commands
    $response .= "\tTHEMES\n";
    $response .= str_pad('create_theme THEME', 40) . "Creates a new theme for development with specified alias\n";
    $response .= str_pad('publish_theme THEME', 40) . "Publishes theme to repository\n";
    $response .= str_pad('list_themes', 40) . "Lists all themes available to the system\n";
    $response .= str_pad('install_theme THEME', 40) . "Downloads theme from repository, and installs on system\n";
    $response .= str_pad('delete_theme THEME', 40) . "Deletes theme from system\n";
    $response .= str_pad('change_theme AREA THEME', 40) . "Changes active theme on AREA (public / members) to the specified theme\n\n";

    // Component commands
    $response .= "\tCOMPONENTS\n";
    $response .= str_pad('create TYPE PACKAGE:[PARENT:]:ALIAS [OWNER]', 40) . "Creates a new component.  See documentation for details\n";
    $response .= str_pad('delete TYPE PACKAGE:[PARENT:]ALIAS', 40) . "Deletes a component.  See documentation for details\n\n";

    // Cache / debug
    $response .= "\tDebug / Cache \n";
    $response .= str_pad('debug LEVEL', 40) . "Sets current debug more, 0 = off, 1 = next request, 2 = always on\n";
    $response .= str_pad('enable_cache', 40) . "Turns cache on.\n";
    $response .= str_pad('disable_cache', 40) . "Turns cache off.\n\n";

    // System maintenance
    $response .= str_pad('server_type TYPE', 40) . "Changes the server type to desired type (eg. all, web, app, etc.)\n";
    $response .= "\tSYSTEM / MAINTENANCE\n";
    $response .= str_pad('add_repo URL [USERNAME] [PASSWORD]', 40) . "Adds new repository to system with optional username / password\n";
    $response .= str_pad('update_repo URL', 40) . "Updates existing repo with new username / password\n";
    $response .= str_pad('update_masterdb', 40) . "Update connection information for master mySQL database\n";
    $response .= str_pad('clear_dbslaves', 40) . "Clear all slave mySQL database servers\n";
    $response .= str_pad('update_rabbitmq', 40) . "Update connection information for RabbitMQ\n\n\n";

    // Github
    $response .= "\tGIT / GITHUB INTEGRATION\n";
    $response .= str_pad('git_init PACKAGE', 40) . "Initializes package as git repository.  Used only once when initially publishing package to Github.\n";
    $response .= str_pad('git_sync PACKAGE', 40) . "Downloads the git repository, and updates the local package as necessary.  This assumes git repository is more up to date than local copy.\n";
    $response .= str_pad('git_compare PACKAGE', 40) . "Compares git repository to local package, and generates git.sh file to update git repository as needed.  This assumes local package is more up to date than git repository.\n\n";

    // Debug
    debug::add(4, "CLI: help", 'info');

    // Return
    return $response;

}

/**
 * List all available packages 
 *
 * Usage: php apex.php list_packages 
 * 
 * @param mixed $vars The arguments from the command line.
 * @param network $client The network.php clinet.  Injected.
 */
public function list_packages($vars, network $client)
{ 

    // Get packages
    $packages = $client->list_packages();

    // Get response
    $response = '';
    foreach ($packages as $vars) { 
        $response .= $vars['alias'] . ' -- ' . $vars['name'] . ' (' . $vars['author_name'] . ')';
    }

    // Debugh   // Debug
    debug::add(4, 'CLI: list_packages', 'info');

    // Return
    return $response;

}

/**
 * Search for package(s) 
 *
 * Searches all repositories configured on the system for a package(s) that 
 * meet the specified search term. Usage:  php apex.php search TERM 
 *
 * @param iterable $vars The command line arguments specified by the user.
 * @param network $client The network.php client.  Injected.
 */
public function search($vars, network $client)
{ 

    // Initialize
    $term = implode(" ", $vars);

    // Search
    $response = $client->search($term);

    // Debug
    debug::add(4, tr("CLI: search -- term: {1}", $term), 'info');

    // Return
    return $response;

}

/**
 * Downloads a package from the repository, and installs it on the system. 
 * Usage:  php apex.php install PACAKGE_ALIAS 
 *
 * @param iterable $vars The command line arguments specified by the user.
 * @param package $package The /app/pkg/package.php class.  Injected.
 */
public   function install($vars, package $package)
{ 

    // Install
    $response = '';
    foreach ($vars as $alias) { 

        // Check if package exists
        if ($row = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", $alias)) { 
            throw new PackageException('exists', $alias);
        }

        // Debug
        debug::add(4, tr("CLI: Starting install of package: {1}", $alias), 'info');

        // Install package
        $package->install($alias);

        // Debug
        debug::add(4, tr("CLI: Complete install of package: {1}", $alias), 'info');

        $response .= "Successfully installed the package, $alias\n";
    }

    // Return
    return $response;

}

/**
 * Scan package.php file, update configuration. 
 *
 * Scans the package.php configuration file of a package, and updates the 
 * database as necessary.  Used during development, when you modify things 
 * like config variables, hashes, or menus. Usage:  php apex.php scan 
 * PACKAGE_ALIAS 
 *
 * @param iterable $vars The command line arguments specified by the user.
 * @param package $package The /app/pkg/package.php class.  Injected.
 */
public function scan($vars, package $package)
{ 

    // Checks
    if (!isset($vars[0])) { 
        throw new PackageException('undefined');
    }

    // Go through packages
    $response = '';
    foreach ($vars as $alias) { 

        // Get package from db
        if (!$row = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", $alias)) { 
            $response .= "The package '$alias' does not exist.\n";
            continue;
        }

        // Scan package
        $client = new package_config($alias);
        $client->install_configuration();

        // Debug
        debug::add(4, tr("CLI: Scanned package: {1}", $alias), 'info');

        // Success
        $response .= "Succesfully scanned the package, $alias\n";
    }

    // Return
    return $response;

}

/**
 * Create package. 
 *
 * Creates a new package.  Inserts a row into the 'internal_packages' table, 
 * and creates the necessary base directories and configuration files. Usage: 
 * php apex.php create_package PACKAGE_ALIAS 
 *
 * @param iterable $vars The command line arguments specified by the user.
 * @param package $package The /app/pkg/package.php class.  Injected.
 * @param network $client The /app/sys/network.php class.  Injected.
 */
public function create_package($vars, package $package, network $client)
{ 

// Initialize
    $pkg_alias = $vars[0] ?? '';
    $repo_id = $vars[1] ?? 0;
    $name = $vars[2] ?? $pkg_alias;

    // Debug
    debug::add(4, tr("CLI: Starting creation of package: {1}", $pkg_alias), 'info');

    // Check if package exists
    if ($row = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", $pkg_alias)) { 
        throw new PackageException('exists', $pkg_alias);
    }

    // Validate alias
    if (!$package->validate_alias($pkg_alias)) { 
        throw new PackageException('invalid_alias', $pkg_alias);
    }

    // Check if package exists in any repos
    $repos = $client->check_package($pkg_alias);

    // Ask to continue if package exists in any repos
    if (count($repos) > 0) { 
        echo "The package '$pkg_alias' already exists in the following repositories:\n";
        foreach ($repos as $repo) { echo "\t$repo\n"; }
        echo "\nAre you sure you want to create the package '$pkg_alias' (yes / no) [no]: "; 
        $ok = strtolower(trim(readline()));
    if ($ok != 'y' && $ok != 'yes') { echo "\nOk.  Exiting.\n"; exit(0); }
    }

    // Get repo ID#, if needed
    if ($repo_id == 0) { 
        $repo_id = $this->get_repo();
    }

    // Create package
    $package_id = $package->create((int) $repo_id, $pkg_alias, $name);

    // Debug
    debug::add(4, tr("CLI: Completed creation of package: {1}", $pkg_alias), 'info');

    // Return
    return "Successfully created the new package '$pkg_alias', and you may begin development.\n\n";

}

/**
 * Delete package. 
 *
 * Delete a package.  Removes the package from the current system, please 
 * deletes it altogether.  Only to be used if you do not plan on developing 
 * this package anymore. Usage:  php apex.php delete_package PACKAGE 
 *
 * @param iterable $vars The command line arguments specified by the user.
 */
public function delete_package($vars)
{ 

    // Debug
    debug::add(4, tr("CLI: Starting deletion of package: {1}", $vars[0]), 'info');

    // Ensure package exists
    if (!$row = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", $vars[0])) { 
        throw new PackageException('not_exists', $vars[0]);
    }

    // Delete package
    $package = new package();
    $package->remove($vars[0]);

    // Debug
    debug::add(4, tr("CLI: Completed deletion of package: {1}", $vars[0]), 'info');

    // Response
    return "Successfully deleted the package, $vars[0]\n";

}

/**
 * Publish package. 
 *
 * Publishes a package to the appropriate repository, from where it can be 
 * instantly installed on other Apex systems. Usage: php apex.php publish 
 * PACKAGE_ALIAS 
 *
 * @param iterable $vars The command line arguments specified by the user.
 */
public function publish($vars)
{ 

    // Checks
    if (!isset($vars[0])) { 
        throw new PackageException('undefined');
    }

    // Publish
    $response = '';
    foreach ($vars as $alias) { 

        // Check package exists
        if (!$row = db::get_row("SELECT * FROm internal_packages WHERE alias = %s", $alias)) { 
            $response .= "Package does not exist in this system, $alias\n";
            continue;
        }

        // Debug
        debug::add(4, tr("CLI: Starting to publish package: {1}", $alias), 'info');

        // Publish
        $client = new package();
        $client->publish($alias);

        // Debug
        debug::add(4, tr("CLI: Completed publishing package: {1}", $alias), 'info');

        // Success message
        $response .= "Successfully published the package, $alias\n";
    }

    // Return
    return $response;

}

/**
 * Create upgrade point. 
 *
 * Creates a new upgrade point, which is stored in 
 * /etc/PACKAGE/upgrades/VERSION/ directory.  Takes a SHA1 hash of all files 
 * pertaining to the package, which is used to check which files were modified 
 * upon publishing the upgrade. Usage:  php apex.php create_upgrade PACKAGE 
 * ]VERSION]
 *
 * @param iterable $vars The command line arguments specified by the user. 
 */
public function create_upgrade($vars)
{ 

    // Set variables
    $pkg_alias = $vars[0] ?? '';
    $version = $vars[1] ?? '';

    // Debug
    debug::add(4, tr("CLI: Starting to create upgrade point for package: {1}", $pkg_alias), 'info');

    // Check package
    if ($pkg_alias == '') { 
        throw new PackageException('undefined');
    }

    // Check for open upgrades
    $count = db::get_field("SELECT count(*) FROM internal_upgrades WHERE package = %s AND status = 'open'", $pkg_alias);
    if ($count > 0) { 
        echo "There are currently open upgrades already on this package.  Are you sure you want to create another upgrade point? (y/n) [n]: ";
        $ok = strtolower(trim(readline()));
        if ($ok != 'y') { echo "Ok, exiting.\n"; exit(0); }
    }

    // Create upgrade
    $client = new upgrade();
    $upgrade_id = $client->create($pkg_alias, $version);

    // Debug
    debug::add(4, tr("CLI: Completed vreating upgrade point for package: {1}", $pkg_alias), 'info');

    // Return response
    $version = db::get_field("SELECT version FROM internal_upgrades WHERE id = %i", $upgrade_id);
    return "Successfully created upgrade point on package $vars[0] to upgrade version $version\n\n";

}

/**
 * Publish upgrade. 
 *
 * Publishes an upgrade to the appropriate repository. Usage: php apex.php 
 * publish_upgrade PACKAGE 
 *
 * @param iterable $vars The command line arguments specified by the user.
 */
public function publish_upgrade($vars)
{ 

    // Set variables
    $pkg_alias = $vars[0] ?? '';

    // Debug
    debug::add(4, tr("CLI: Start publishing upgrade point for package: {1}", $pkg_alias), 'info');

    // Initial checks
    if ($pkg_alias == '') { 
        throw new PackageException('undefined');
    }

    // Get package
    if (!$row = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", $vars[0])) { 
        throw new PackageException('not_exists', $vars[0]);
    }

    // Check number of open upgrades
    $num = db::get_field("SELECT count(*) FROM internal_upgrades WHERE package = %s AND status = 'open'", $pkg_alias);
    if ($num == 0) { 
        throw new PackageException('no_open_upgrades', $pkg_alias);

    // Ask which upgrade to publish
    } elseif ($num > 1) { 

        // Echo message
        echo "More than one open upgrade was found for this package.  Please specify which upgrade you would like to publish.\n\n";

        $available = array(); $x=1;
        $rows = db::query("SELECT * FROM internal_upgrades WHERE package = %s AND status = 'open' ORDER BY id", $pkg_alias);
        foreach ($rows as $row) { 
            echo "[$x] v$row[version]\n";
            $available[$x] = $row['id'];
        $x++; }

        // Ask for upgrade
        while (1 == 1) { 
            echo "Upgrade to publish: "; $upgrade_id = trim(readline());
            if (isset($available[$upgrade_id])) { break; }
            echo "You did not specify an upgrade to publish.\n\n";
        }

        // Get upgrade row
        if (!$upgrade = db::get_idrow('internal_upgrades', $upgrade_id)) { 
            throw new UpgradeException('not_exists', $upgrade_id);
        }

    // Get one available upgrade
    } else { 
        $upgrade = db::get_row("SELECT * FROM internal_upgrades WHERE package = %s AND status = 'open' LIMIT 0,1", $pkg_alias);
    }

    // Publish upgrade
    $client = new upgrade();
    $is_git = $client->publish((int) $upgrade['id']);

    // Debug
    debug::add(4, tr("CLI: Completed publishing upgrade point for package: {1}", $pkg_alias), 'info');

    // Set response
    $response = "Successfully published the appropriate upgrade for package, $pkg_alias\n\n";
    if ($is_git == 1) { 
        $response .= "To complete the upgrade, and publish to the git repository, run the following command:\n";
        $response .= "\tcd " . SITE_PATH . "/src/$pkg_alias/git; ./git.sh\n\n";
    }

    // Ask to create new upgrade point
    $ok = $vars[1] ?? '';
    if ($ok == '') { 
        echo "Would you like to create a new upgrade point? (y\n) [y]: ";
        $ok = strtolower(trim(readline()));
    }

    // Create upgrade point, if needed
    if ($ok == '' || $ok == 1 || strtolower($ok) == 'y') { 
        $response .= $this->create_upgrade(array($pkg_alias));
    }

    // Return
    return $response;

}

/**
 * Check for upgrades 
 *
 * Usage:  php apex.php check_upgrades [PACKAGE] 
 *
 * @param iterable $vars The command line arguments specified by the user.
 * @param network $client The /app/sys/network.php class.  Injected.
 */
public function check_upgrades($vars, network $client)
{ 

    // Get packages
    $packages = array();
    foreach ($vars as $alias) { 
        $packages[] = $alias;
    }

    // Check upgrades
    $upgrades = $client->check_upgrades($packages);

    // Go through upgrades
    $results = '';
    foreach ($upgrades as $pkg_alias => $version) { 

        // Get package
        if (!$row = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", $pkg_alias)) { 
            continue;
        }

        // Add to results
        $results .= '[' . $pkg_alias . '] ' . $row['name'] . ' v' . $version . "\n";
    }

    // Give response
    if ($results == '') { 
        $response = "No upgrades were found for any installed packages.\n";
    } else { 
        $response = "The following available upgrades were found:\n\n";
        $response .= $results . "\n";
        $response .= "If desired, you may install the upgrades with: php apex.php upgrade\n";
    }

    // Debug
    debug::add(4, "CLI: check_upgrades done", 'info');

    // Return
    return $response;

}

/**
 * Install upgrades 
 *
 * Usage:  php apex.php upgrade [PACKAGE] 
 *
 * @param iterable $vars The command line arguments specified by the user.
 * @param network $client The /app/sys/network.php class.  Injected.
 * @param upgrade $upgrade_client The /app/pkg/upgrade.php class.  Injected.
 */
public function upgrade($vars, network $client, upgrade $upgrade_client)
{ 

    // Get available upgrades, if needed
    if (count($vars) == 0) { 
        $upgrades = $client->check_upgrades();
        $vars = array_keys($upgrades);
    }

    // Go through packages
    $response = '';
    foreach ($vars as $pkg_alias) { 

        // Debug
        debug::add(4, tr("CLI: Starting upgrade of package: {1}", $pkg_alias), 'info');

        // Install upgrades
        $new_version = $upgrade_client->install($pkg_alias);

        // Debug
        debug::add(4, tr("CLI: Completed upgrade of package: {1} to version {2}", $pkg_alias, $new_version), 'info');

        // Add to response
        $response .= "Successfully upgraded the packages $pkg_alias to v$new_version\n";
    }

    // Return
    return $response;

}

/**
 * Rollback an upgrade
 *
 * Usage:  php apex.php rollback PACKAGE [VERSION]
 *
 * @param iterable $vars The command line arguments specified by the user.
 */
public function rollback($vars)
{

    // Checks
    $pkg_alias = $vars[0] ?? '';
    $version = $vars[1] ?? '';

    // Get package
    if (!$pkg = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", $pkg_alias)) { 
        throw new PackageException('not_exists', $pkg_alias);
    }

    // Get rollback version, if needed
    if ($version == '') { 

        // Ask for rollback
        echo "Please select which version you would like to revert to.\n\n";
        // Go through previously installed upgrades
        $x=1;
        $prev_versions = array();
        $rows = db::query("SELECT * FROM internal_upgrades WHERE package = %s AND status = 'installed' AND prev_version != '' ORDER BY id DESC LIMIT 0,25", $pkg_alias);
        foreach ($rows as $row) { 
            echo "    [$x] v" . $row['prev_version'] . "\n";
            $prev_versions[(string) $x] = $row['prev_version'];
        $x++; }

        // Get rollback version
        do {
            echo "Version: ";
            $version_num = trim(readline());
            $version = $prev_versions[$version_num] ?? '';
        } while ($version == '');

    }

    // Debug
    debug::add(2, tr("Starting to rollback package {1} to version {2}", $pkg_alias, $version));

    // Rollback package
    $upgrade = app::make(upgrade::class);
    $upgrade->rollback($pkg_alias, $version);

    // Debug
    debug::add(1, tr("Successfully rolled back package {1} to version {2}", $pkg_alias, $version));

}


/**
 * create a new theme 
 *
 * Usage:  php apex.php create_theme ALIAS [AREA] 
 *
 * @param iterable $vars The command line arguments specified by the user.
 */
public function create_theme($vars)
{ 

    // Set variables
    $alias = strtolower($vars[0]) ?? '';
    $area = $vars[1] ?? 'public';
    $repo_id = $vars[2] ?? 0;

    // Debug
    debug::add(4, tr("CLI: Start theme creation, alias: {1}, area: {2}", $alias, $area), 'info');

    // Get repo ID
    if ($repo_id == 0) { 
        $repo_id = $this->get_repo();
    }

    // Create theme
    $theme = new theme();
    $theme->create($alias, (int) $repo_id, $area);

    // Echo message
    $response = "Successfully created new theme, $alias.  New directories to implment the theme are now available at:\n\n";
    $response .= "\t/views/themes/$alias\n";
    $response .= "\t/public/themes/$alias\n\n";

    // Debug
    debug::add(4, tr("CLI: Completed theme creation, alias: {1}, area: {2}", $alias, $area), 'info');

    // Return
    return $response;

}

/**
 * Initialize a theme
 *
 * Usage:  php apex.php init_theme ALIAS
 *
 * @param iterable $vars The command line arguments specified by the user.
 */
public function init_theme($vars)
{

    // Check theme
    $theme_alias = $vars[0] ?? '';
    if (!is_dir(SITE_PATH . '/views/themes/' . $theme_alias)) { 
        throw new ThemeException('not_exists', $theme_alias);
    }

    // Debug
    debug::add(1, tr("Starting to initialize theme {1}", $theme_alias));

    // Set variables
    $client = app::make(theme::client);
    $theme_dir = SITE_PATH . '/views/themes/' . $theme_alias;
    $dirs = array('sections', 'tpl', 'layouts');

    // Go through dirs
    foreach ($dirs as $dir) { 
        if (!is_dir("$theme_dir/$dir")) { continue; }

        // Get files
        $files = io::parse_dir("$theme_dir/$dir");
        foreach ($files as $file) { 
            if (!preg_match("/\.tpl$/", $file)) { continue; }
            $client->init_file("$theme_dir/$dir/$file");
        }
    }

    // Debug
    debug::add(1, tr("Successfully initialized theme {1}", $theme_alias));

    // Return
    return "Successfully initialized the theme $theme_alias, and all files have been updated appropriately.";

}

/**
 * Publish a theme to a repository. 
 *
 * Usage:  php apex.php publish_theme ALIAS 
 *
 * @param iterable $vars The command line arguments specified by the user.
 */
public function publish_theme($vars)
{ 

    // Debug
    debug::add(4, tr("CLI: Start publishing theme: {1}", $vars[0]), 'info');

    // Upload theme
    $theme = new theme();
    $theme->publish($vars[0]);

    // Debug
    debug::add(4, tr("CLI: Completed publishing theme: {1}", $vars[0]), 'info');

    // Give response
    return "Successfully published the theme, $vars[0]\n";

}

/**
 * List all available themes 
 *
 * Usage:  php apex.php list_themes 
 *
 * @param iterable $vars The command line arguments specified by the user.
 * @param network $client The /app/sys/network.php class.  Injected.
 */
public function list_themes($vars, network $client)
{ 

    // Get themes
    $themes = $client->list_packages('theme');

    // Check for no themes
    if (count($themes) == 0) { 
        return "No themes are avilable from any repositories.\n";
    }

    // Go through themes
    $public_themes = ''; $members_themes = '';
    foreach ($themes as $alias => $vars) { 
        $line = $alias . ' -- ' . $vars['name'] . ' (' . $vars['author_name'] . ' <' . $vars['author_email'] . ">\n";
        if ($vars['area'] == 'members') { 
            $members_themes .= $line;
        } else { 
            $public_themes .= $line;
        }
    }

    // Get response
    $response = '';
    if ($public_themes != '') { 
        $response .= "--- Public Site Themes ---\n";
        $response .= "$public_themes\n";
    }
    if ($members_themes != '') { 
        $response .= "--- Member Area Themes ---\n";
        $response .= "$members_themes\n";
    }

    // Debug
    debug::add(4, "CLI: list_themes", 'info');

    // Return
    return $response;

}

/**
 * Download and install a theme 
 *
 * Usage:  php apex.php install_theme THEME_ALIAS 
 *
 * @param iterable $vars The command line arguments specified by the user.
 */
public function install_theme($vars)
{ 

    // Set variables
    $theme_alias = $vars[0] ?? '';

    // Debug
    debug::add(4, tr("CLI: Start installing theme: {1}", $theme_alias), 'info');


    // Install theme
    $theme = new theme();
    $theme->install($theme_alias);

    // Debug
    debug::add(4, tr("CLI: Completed installing theme: {1}", $theme_alias), 'info');

    // Return
    return "Successfully downloaded and installed the theme, $theme_alias\n";

}

/**
 * Delete theme 
 *
 * Usage:  php apex.php delete_theme THEME_ALIAS 
 *
 * @param iterable $vars The command line arguments specified by the user.
 */
public function delete_theme($vars)
{ 

    // Set variables
    $theme_alias = $vars[0] ?? '';

    // Debug
    debug::add(4, tr("CLI: Start theme deletion: {1}", $theme_alias), 'info');

    // Delete theme
    $theme = new theme();
    $theme->remove($theme_alias);

    // Debug
    debug::add(4, tr("CLI: Completed theme deletion: {1}", $theme_alias), 'info');

    // Return
    return "Successfully deleted the theme, $theme_alias\n";

}

/**
 * Change theme on an area to another theme. 
 *
 * Usage:  php apex.php change_theme AREA THEME_ALIAS 
 *
 * @param iterable $vars The command line arguments specified by the user.
 */
public function change_theme($vars)
{ 

    // Set variables
    $area = $vars[0] ?? '';
    $theme_alias = $vars[1] ?? '';

    // Perform checks
    if ($area != 'public' && $area != 'members' && $area != 'admin') { 
        throw new ApexException('error', "Invalid area specified, {1}", $area);
    } elseif (!$row = db::get_row("SELECT * FROM internal_themes WHERE alias = %s", $theme_alias)) { 
        throw new ThemeException('not_exists', $theme_alias);
    }

    // Update theme
    app::change_theme($area, $theme_alias);

    // Debug
    debug::add(4, tr("CLI: Changed theme on area '{1}' to theme: {2}", $area, $theme_alias), 'info');

    // Return
    return "Successfully changed the theme of area $area to the theme $theme_alias\n";

}

/**
 * Create new component. 
 *
 * Creates a new component (eg. htmlfunc, modal, template, lib, etc.) and 
 * should be used during development.  Do not manually create the files on the 
 * server, and instead use this create command to ensure proper records are 
 * added to the database for packaging. Usage:  php apex.php create TYPE 
 * PACKAGE:[PARENT:]ALIAS OWNER php apex.php create template URI OWNER TYPE is 
 * the component type (eg. htmlfunc, table, form). PACKAGE is required, and is 
 * the package alias the component is being created under. PARENT is only 
 * required for the types 'tabpage', 'adapter', and 'trigger'. ALIAS is the 
 * alias of the new component. OWNER is only required for type 'template', 
 * 'trigger', 'adapter', and 'tabpage'.  This is the package who owns the 
 * package, even though the component may reside in another package. URI is 
 * only required for 'template', and is the URI of the new template. 
 *
 * @param iterable $vars The command line arguments specified by the user.
 */
public function create($vars)
{ 

    // Set variables
    $type = strtolower($vars[0]) ?? '';
    $comp_alias = $vars[1] ?? '';
    $owner = $vars[2] ?? '';

    // Debug
    debug::add(4, tr("CLI: Start component creation, type: {1}, component alias: {2}, owner: {3}", $type, $comp_alias, $owner), 'info');

    // Perform checks
    if (!in_array($type, array_keys(COMPONENT_TYPES))) { 
        throw new ComponentException('invalid_type', $type);
    } elseif ($type != 'view' && ($comp_alias == '' || !preg_match("/^(\w+):(\w+)/", $comp_alias)) ){ 
        throw new ComponentException('invalid_comp_alias', $type, $comp_alias);
    }

    // Create component
    list($type, $alias, $package, $parent) = pkg_component::create($type, $comp_alias, $owner);

    // Get files
    $files = components::get_all_files($type, $alias, $package, $parent);

    // Set response
    $response = "Successfully created new $type, $comp_alias.  New files have been created at:\n\n";
    foreach ($files as $file) { 
        $response .= "\t\t$file\n";
    }

    // Debug
    debug::add(4, tr("CLI: Completed component creation, type: {1}, component alias: {2}, owner: {3}", $type, $comp_alias, $owner), 'info');

    // Return
    return $response;

}

/**
 * Delete components. 
 *
 * Delete a component from the system, including file and records from 
 * database. Usage:  php apex.php delete TYPE PACKAGE:[PARENT:]ALIAS php 
 * apex.php delete template URI 
 *
 * @param iterable $vars The command line arguments specified by the user.
 */
public function delete($vars)
{ 

    // Initialize
    $type = strtolower($vars[0]);

    // Debug
    debug::add(4, tr("CLI: Start component deletion, type: {1}, component alias: {2}", $type, $vars[1]), 'info');

    // Check if component exists
    if (!list($package, $parent, $alias) = components::check($type, $vars[1])) { 
        throw new ComponentException('not_exists', $type, $vars[1]);
    }

    // Delete
    pkg_component::remove($type, $vars[1]);

    // Debug
    debug::add(4, tr("CLI: Completed component deletion, type: {1}, component alias: {2}", $type, $vars[1]), 'info');

    // Return
    return "Successfully deleted the component of type $type, with alias $vars[1]\n\n";

}

/**
 * Create CRUD scaffolding
 *
 * @param array $vars The arguments passed via CLI.
 */
public function crud($vars)
{

    // Check for crud.yml file
    $file = $vars[0] ?? 'crud.yml';
    if (!file_exists(SITE_PATH . '/' . $file)) { 
        throw new ApexException('error', "No file exists within the installation directory at, $file");
    }

    // Create CRUD scaffolding
    $client = app::make(crud::class);
    list($alias, $package, $files) = $client->create($file);

    // Set response
    $response = "Successfully created new CRUD components with alias '$alias' under the package '$package'.  The following files have been created, and may be modified as necessary:\n\n";
    foreach ($files as $file) { 
        $response .= "    $file\n";
    }

    // Return
    return "$response\n";

}

/**
 * Update session debugging
 *
 * Update the debug variable, telling them system whether or not to save debug 
 * information. 0 - Debugging off 1 - Debugging on, but only for next request 
 * 2 - Debugging on, do not turn off Usage: php apex.php debug NUM 
 *
 * @param iterable $vars The command line arguments specified by the user.
 */
public function debug($vars)
{ 

    // Update config
    app::update_config_var('core:debug', $vars[0]);

    // Debug
    debug::add(4, tr("CLI: Updated debug mode to {1}", $vars[0]), 'info');

    // Return
    return "Successfully changed debugging mode to $vars[0]\n";

}

/**
 * Change server mode 
 *
 * Change the server mode between development / production, and the debug 
 * level Usage: php apex.php mode [devel|prod] [DEBUG_LEVEL] 
 *
 * @param iterable $vars The command line arguments specified by the user.
 */
public function mode($vars)
{ 

    // CHeck
    $mode = strtolower($vars[0]);
    if ($mode != 'devel' && $mode != 'prod') { 
        throw new ApexException('error', "You must specify the mode as either 'devel' or 'prod'");
    }

    // Update config
    app::update_config_var('core:mode', $mode);
    if (isset($vars[1])) { 
        app::update_config_var('core:debug_level', $vars[1]);
    }

    // Debug
    $level = $vars[1] ?? app::_config('core:debug_level');
    debug::add(4, tr("CLI: Updated server mode to {1}, debug level to {2}", $mode, $level), 'info');

    // Return
    return "Successfully updated server mode to $mode, and debug level to $level\n";

}

/**
 * Change server type 
 *
 * Changes the server type, such as whether or not it's an all-in=one, 
 * back-end application, messaging, or front-end web server, etc.  This simply 
 * modifies how messaging via RabbitMQ works. Usage:  php apex.php server_type 
 * TYPE
 *
 * @param iterable $vars The command line arguments specified by the user. 
 */
public function server_type($vars) 
{ 

    // Check
    $type = $vars[0] ?? '';
    if (!in_array($type, array('all','web','app','dbs','dbm','msg'))) { 
        throw new ApexException('error', "Invalid server type, {1}", $type);
    }

    // Update config
    app::update_config_var('core:server_type', $type);

    // Debug
debug::add(2, tr("CLI: Updated server type to {1}", $type));

    // Return
    return "Successfully updated server type to $type\n";

}

/**
 * Add a new repopository 
 *
 * Adds a new repository into the system, which then begins getting checked 
 * when searching for / install packages, themes and upgrades. Usage:  php 
 * apex.php add_repo URL [USERNAME] [PASSWORD] 
 *
 * @param iterable $vars The command line arguments specified by the user.
 * @param network $client The /app/sys/network.php class.  Injected.
 */
public function add_repo($vars, repo $client)
{ 

    // Set variables
    $host = $vars[0] ?? '';
    $username = $vars[1] ?? '';
    $password = $vars[2] ?? '';

    // Initial checks
    if ($host == '') { 
        throw new RepoException('not_exists', 0, $host);
    }

    // Add repo
    $client->add($host, $username, $password);

    // Debug
    debug::add(4, tr("CLI: Added new repository, {1}", $host), 'info');

    // Return
    return "Successfully added new repository, $host\n";

}

/**
 * Update a repo with username and password 
 *
 * @param iterable $vars The command line arguments specified by the user.
 */
public function update_repo($vars, repo $client)
{ 

    // Check repo
    if (!$row = db::get_row("SELECT * FROM internal_repos WHERE host = %s", $vars[0])) { 
        throw new RepoException('host_not_exists', 0, $vars[0]);
    }

    // Check vars
    $username = $vars[1] ?? '';
    $password = $vars[2] ?? '';

    // Get username / password, if needed
    if ($username == '') { 
        echo "Username: "; $username = trim(readline());
    }
    if ($password == '') { 
        echo "Password: "; $password = trim(readline());
    }

    // Update repo
    $client->update((int) $row['id'], $username, $password);

    // Debug
    debug::add(4, tr("CLI: Updated repository login information, host: {1}", $vars[0]), 'info');

    // Give response
    return "Successfully updated repo with new username and password.\n";

}

/**
 * Update master database info 
 *
 * Usage:  php apex.php update_masterdb 
 *
 * @param iterable $vars The command line arguments specified by the user.
 */
public function update_masterdb($vars)
{ 

    // Get info
    echo "DB Name: "; $dbname = trim(readline());
    echo "DB User: "; $dbuser = trim(readline());
    echo "DB Pass: "; $dbpass = trim(readline());
    echo "DB Host [localhost]: "; $dbhost = trim(readline());
    echo "DB Port [3306]: "; $dbport = trim(readline());
    if ($dbhost == '') { $dbhost = 'localhost'; }
    if ($dbport == '') { $dbport = '3306'; }

    // Read only user info
    echo "\n----- Optional Read Only Info -----\n\n";
    echo "Read-Only User: "; $dbuser_readonly = trim(readline());
    echo "Read-Only Pass: "; $dbpass_readonly = trim(readline());

    // Set vars
    $vars = array(
        'dbname' => $dbname,
        'dbuser' => $dbuser,
        'dbpass' => $dbpass,
        'dbhost' => $dbhost,
        'dbport' => $dbport,
        'dbuser_readonly' => $dbuser_readonly,
        'dbpass_readonly' => $dbpass_readonly
    );

    // Update redis
    redis::del('config:db_master');
    redis::hmset('config:db_master', $vars);

    // Debug
    debug::add(4, "Updated master database connection information", 'info');

    // Return
    return "SUccessfully updated master database information.\n";

}

/**
 * Clear all db slave servers 
 *
 * Usage:  php apex.php clear_dbslaves 
 *
 * @param iterable $vars The command line arguments specified by the user.
 */
public function clear_dbslaves()
{ 

    // Delete
    redis::del('config:db_slaves');

    // Debug
    debug::add(4, "CLI: Removed all database slave servers", 'info');

    // Return
    return "Successfully cleared all database slave servers.\n";

}

/**
 * Update RabbitMQ info 
 *
 * Usage:  php apex.php update_rabbitmq 
 *
 * @param iterable $vars The command line arguments specified by the user.
 */
public function update_rabbitmq()
{ 

    // Get info
    echo "Host [localhost]: "; $host = trim(readline());
    echo "User [guest]: "; $user = trim(readline());
    echo "Pass [guest]: "; $pass = trim(readline());
    echo "Port [5672]: "; $port = trim(readline());

    // Set default variables
    if ($host == '') { $host = 'localhost'; }
    if ($user == '') { $user = 'guest'; }
    if ($pass == '') { $pass = 'guest'; }
    if ($port == '') { $port = '5672'; }

    // Set vars
    $vars = array(
        'host' => $host,
        'user' => $user,
        'pass' => $pass,
        'port' => $port
    );

    // Update redis
    redis::del('config:rabbitmq');
    redis::hmset('config:rabbitmq', $vars);

    // Debug
    debug::add(4, "CLI: Updated RabbitMQ connection information", 'info');

    // Return
    return "Successfully updated RabbitMQ connection information.\n";

}

/**
 * Compile Apex Core 
 *
 * Compiles the core Apex framework.  This is generally only needed by the 
 * Apex development team, and basically packages Apex to exactly what you see 
 * in the Github repository. Usage:  php apex.php compile_core 
 *
 * @param iterable $vars The command line arguments specified by the user.
 */
public function compile_core($vars)
{ 

    // Compile
    $client = new package();
    $destdir = $client->compile_core();

    // Debug
    debug::add(4, "CLI: Compiled core Apex framework", 'info');

    // Return
    return  "Successfully compiled the core Apex framework, and it is located at $destdir\n\n";

}

/**
 * Enable the cache
 *
 * @param iterable $vars Nothing.
 */
public function enable_cache(...$vars)
{

    // Return cache on
    app::update_config_var('core:cache', 1);

    // Return
    return "Successfully enabled the cache.";

}

/**
 * Disable the cache
 *
 * @param iterable $vars Nothing.
 */
public function disable_cache(...$vars)
{

    // Disable cache
    app::update_config_var('core:cache', 0);

    // Return
    return "Successfully disabled the cache";

}

/**
 * Obtain a repoistory 
 *
 * Get a repository.  Lists a list of all repos on the system, and has user 
 * choose one. 
 */
private function get_repo()
{ 

    // Check number of repos
    $count = db::get_field("SELECT count(*) FROM internal_repos WHERE is_active = 1");
    if ($count == 0) { 
        throw new RepoException('no_repos_exist');
    }

    // Ask to select repo
    if ($count > 1) { 

        // List repos
        echo "\nAvailable Repositories:\n";
        $rows = db::query("SELECT * FROM internal_repos WHERE is_active = 1 ORDER BY id");
        foreach ($rows as $row) { 
            echo "\t[" . $row['id'] . "] $row[name] ($row[host])\n";
        }
        echo "\nWhich repository to save package on? ";
        $repo_id = trim(readline());

    // Get the only available repo
    } else { 
        $repo_id = db::get_field("SELECT id FROM internal_repos WHERE is_active = 1");
    }

    // Ensure repo exists
    if (!$row = db::get_idrow('internal_repos', $repo_id)) { 
        throw new RepoException('not_exists', $repo_id);
    }

    // Return
    return (int) $repo_id;

}

/**
 * Git initialize repo
 *
 * Will initialize a local Github repository for the specified 
 * package within the /src/PACKAGE/git/ directory.  This requires 
 * a $github_repo_url variable to defined within the package 
 * configuration at /etc/PACKAGE/package.php file.
 */
public function git_init($vars)
{

    // Initialize GIthub repo
    $client = app::make(github::class);
    $client->init($vars[0]);

    // Set response
    $response = "Successfully initialized local Github repo for package $vars[0].  To complete initialization, please run the following command in termina.\n\n";
    $response .= "\tcd " . SITE_PATH . "/src/$vars[0]/git; ./git.sh\n\n";

    // Return
    return $response;

}

/**
    * Sync local code from GIthub repository.
 *
 * Downloads the GIthub repository for the specified package into a tmp 
 * directory, and copies / updates all local code within the local 
 * Apex installation with any newer code.
 */
public function git_sync($vars)
{

    // Initialize
    $pkg_alias = strtolower($vars[0]);

    // Sync the repo
    $client = app::make(github::class);
    $client->sync($pkg_alias);

    // Return
    return "Successfully synced package with its git repository, $pkg_alias\n";


}

/**
 * Compare git repos.
 *
 * Downloads the remote git repo, compares it to the local filesystem, and generates the necessary 
 * git.sh file to add all necessary files to the next push / commit, and 
 * ensure the git repo is a mirror copy of the local filesystem.
 */
public function git_compare($vars) 
    {

    // Initialize
    $pkg_alias = strtolower($vars[0]);

    // Compare git repo
    $client = app::make(github::class);
    $client->compare($pkg_alias);

    // Return
    return "Successfully compared git repo with local filesystem for package, $pkg_alias.  There is now a new file located at /src/$pkg_alias/git/git.sh, to be executd and will ensure the git repo is a mirror of the local filesystem.\n";

}

}

