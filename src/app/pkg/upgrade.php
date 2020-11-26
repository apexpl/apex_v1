<?php
declare(strict_types = 1);

namespace apex\app\pkg;

use apex\app;
use apex\libc\db;
use apex\libc\debug;
use apex\app\sys\network;
use apex\libc\io;
use apex\libc\components;
use apex\app\exceptions\PackageException;
use apex\app\exceptions\RepoException;
use apex\app\exceptions\UpgradeException;
use apex\app\pkg\package;
use apex\app\pkg\package_config;
use apex\app\pkg\pkg_component;
use apex\app\pkg\github;


/**
 * Handles all upgrade functions, including create upgrade point, publish, 
 * download, install, and rollback. 
 */
class upgrade
{




    // Properties
    protected $upgrade_dir;
    protected $file_num = 1;
    protected $toc = array();
    protected $file_hash = array();
    protected $components = array();

/**
 * Create upgrade point 
 *
 * Create a new upgrade point.  Upon creation, all files within the package 
 * will be hashed, then upon publication, all modified files will be included. 
 *  
 *
 * @param string $pkg_alias The alias of the package to create upgrade point for
 * @param string $version The version of the upgrade.  Optional, if blank increments the third element of the current package version by 1.
 *
 * @return int The ID# of the newly created upgrade point
 */
public function create(string $pkg_alias, string $version = '')
{ 

    // Get package
    if (!$row = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", $pkg_alias)) { 
        throw new PackageException('not_exists', $pkg_alias);
    }

    // Debug
    debug::add(3, tr("Starting create upgrade point for package, {1}", $pkg_alias));

    // Get version, if one not defined
    if ($version == '') { 
        $v = explode(".", $row['version']);
        $version = $v[0] . '.' . $v[1] . '.' . ++$v[2];
    }

    // Check version if formatted properly
    if (!preg_match("/^(\d+)\.(\d+)\.(\d+)$/", $version)) { 
        throw new UpgradeException('invalid_version');
    }

    // Add to db
    db::insert('internal_upgrades', array(
        'status' => 'open',
        'package' => $pkg_alias,
        'version' => $version)
    );
    $upgrade_id = db::insert_id();

    //Create directiory
    $this->upgrade_dir = SITE_PATH . '/etc/' . $pkg_alias . '/upgrades/' . $version;
    io::create_dir($this->upgrade_dir);
    file_put_contents("$this->upgrade_dir/install.sql", "");
    file_put_contents("$this->upgrade_dir/rollback.sql", "");

    // Save upgrade.php file
    $conf = base64_decode('PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4OwoKdXNlIGFwZXhcYXBwOwp1c2UgYXBleFxsaWJjXGRiOwp1c2UgYXBleFxsaWJjXHJlZGlzOwp1c2UgYXBleFxsaWJjXGRlYnVnOwoKCi8qKgogKiBDbGFzcyB0aGF0IGNvbnRhaW5zIGFueSBuZWNlc3NhcnkgUEhQIHRvIGJlIHNleGVjdXRlZCBkdXJpbmcgdGhlIAogKiBpbnN0YWxsYXRpb24gb2YgdGhpcyB1cGdyYWRlLgogKi8KY2xhc3MgdXBncmFkZV9+cGFja2FnZX5ffnZlcnNpb25+CnsKCi8qKgogKiBJbnN0YWxsIGJlZm9yZS4gIFRoaXMgY29kZSBpcyBleGVjdXRlZCBiZWZvcmUgYW55IGZpbGVzIC8gY29tcG9uZW50cwogKiBhcmUgdXBkYXRlZC4KICovCnB1YmxpYyBmdW5jdGlvbiBpbnN0YWxsX2JlZm9yZSgpCnsKCgp9CgovKioKICogSW5zdGFsbCBhZnRlci4gIFRoaXMgY29kZSBpcyBleGVjdXRlZCBhZnRlciBhbGwgZmlsZXMgLyBjb21wb25lbnRzIAogKiBoYXZlIGJlZW4gdXBncmFkZWQuCiAqLwpwdWJsaWMgZnVuY3Rpb24gaW5zdGFsbF9hZnRlcigpCnsKCgp9CgovKioKICogUm9sbGJhY2suLiAgVGhpcyBjb2RlIGlzIGV4ZWN1dGVkIHVwb24gdGhlIHVwZ3JhZGUgYmVpbmcgcm9sbGVkIGJhY2suCiAqLwpwdWJsaWMgZnVuY3Rpb24gcm9sbGJhY2soKQp7Cgp9Cgp9Cgo=');
    $conf = str_replace("~package~", $pkg_alias, $conf);
    $conf = str_replace("~version~", str_replace(".","_", $version), $conf);
    file_put_contents("$this->upgrade_dir/upgrade.php", $conf);

    // Debug
    debug::add(4, tr("Successfully added new upgrade point to database for package, {1}", $pkg_alias));

    // Create file hash
    $this->create_file_hash((int) $upgrade_id);

    // Debug
    debug::add(1, tr("Successfully created new upgrade point for package, {1}", $pkg_alias));

    // Return
    return $upgrade_id;

}

/**
 * Create SHA1 file hash 
 *
 * Create a SHA1 hash of all files pertaining to the package.  This is used 
 * for upgrades, as it hashes ( all files upon creating an upgrade point, then 
 * checks against the current hashes when publish the upgrade to see which 
 * files were modified. 
 *
 * @param int $upgrade_id The ID# of the upgrade files are being hashed for.
 */
public function create_file_hash(int $upgrade_id)
{ 

    // Get package
    if (!$package = db::get_field("SELECT package FROM internal_upgrades WHERE id = %i", $upgrade_id)) { 
        throw new UpgradeException('not_exists', $upgrade_id);
    }

    // Delete existing hashes
    db::query("DELETE FROM internal_file_hashes WHERE upgrade_id = %i", $upgrade_id);

    // Debug
    debug::add(4, tr("Starting to create file hash for upgrade for package, {1}", $package));

    // Compile package
    $client = app::make(package::class, ['pkg_alias' => $package]);
    $tmp_dir = $client->compile($package);

    // Go through all files
    $files = io::parse_dir($tmp_dir);
    foreach ($files as $file) { 

        // Add to database
        db::insert('internal_file_hashes', array(
            'is_system' => 0, 
            'upgrade_id' => $upgrade_id,
            'filename' => $file, 
            'file_hash' => sha1_file("$tmp_dir/$file"))
        ); 
    }

    // Debug
    debug::add(3, tr("Successfully compiled file hash for upgrade point of package, {1}", $package));

}

/**
 * Compile upgrade 
 *
 * Compiles an upgrade.  Goes through the SHA1 hashes created against each 
 * file when the upgrade point was created, and determines which components / 
 * files have been added, updated, and deleted.  Compiles them as necessary to 
 * be uploaded to the repository. 
 *
 * @param int $upgrade_id The ID# from the 'internal_upgrades' file which to compile
 *
 * @return Returns the filename or the zip archive, which is stored in the system tmp directory.
 */
public function compile($upgrade_id)
{ 

    // Get upgrade from DB
    if (!$upgrade = db::get_idrow('internal_upgrades', $upgrade_id)) { 
        throw new UpgradeException('not_exists', $upgrade_id);
    }
    if ($upgrade['status'] != 'open') { 
        throw new UpgradeException('not_open', $upgrade_id);
    }

    // Debug
    debug::add(4, tr("Compiling upgrade for package {1}, version {2}", $upgrade['package'], $upgrade['version']));

    // Create tmp directory
        $upgrade_dir = sys_get_temp_dir() . '/apex_upgrade_' . $upgrade['package'];
    io::create_blank_dir($upgrade_dir);

    // Get current file hash
    $file_hash = db::get_hash("SELECT filename,file_hash FROM internal_file_hashes WHERE upgrade_id = %s", $upgrade_id);

    // Compile package
    $client = app::make(package::class, ['pkg_alias' => $upgrade['package']]);
    $tmp_dir = $client->compile($upgrade['package']);

    // Go through all files
    $files = io::parse_dir($tmp_dir);
    foreach ($files as $file) { 

        // Get hashes
        $hash = sha1_file("$tmp_dir/$file");
        $chk_hash = $file_hash[$file] ?? '';
        if ($hash == $chk_hash) { continue; }

        // Copy o ver file
        io::create_dir(dirname("$upgrade_dir/$file"));
        copy("$tmp_dir/$file", "$upgrade_dir/$file");
    }

    // Debug
    debug::add(5, tr("Compiling upgrade, went through all existing components, package {1}, version {2}", $upgrade['package'], $upgrade['version']));

    // Get deleted components
    $deleted = array();
    foreach ($file_hash as $file => $hash) { 
        if (file_exists("$tmp_dir/$file")) { continue; }
        $deleted[] = $file;
    }
    file_put_contents("$upgrade_dir/deleted.json", json_encode($deleted));

    // Copy over basic upgrade files
    $etc_dir = SITE_PATH . '/etc/' . $upgrade['package'] . '/upgrades/' . $upgrade['version'];
    $files = array('upgrade.php', 'install.sql', 'rollback.sql');
    foreach ($files as $file) { 
        if (!file_exists($etc_dir . '/' . $file)) { continue; }
        copy($etc_dir . '/' . $file, "$upgrade_dir/$file");
    }

    // Update version in package.php, if core package
    if ($upgrade['package'] == 'core') { 
        io::create_dir("$upgrade_dir/etc");
        $text = str_replace("~version~", $upgrade['version'], file_get_contents(SITE_PATH . '/etc/core/package.php'));
        file_put_contents("$upgrade_dir/etc/package.php", $text);
    }

    // Archive file
    $zip_file = 'apex_upgrade_' . $upgrade['package'] . '-' . str_replace(".", "_", $upgrade['version']) . ".zip";
    $archive_file = sys_get_temp_dir() . '/' . $zip_file;
    io::create_zip_archive($upgrade_dir, $archive_file);

    // Clean up
    io::remove_dir($upgrade_dir);
    io::remove_dir($tmp_dir);

    // Debug
    debug::add(4, tr("Successfully compiled upgrade, and archived it at {1}", $archive_file));

    // Return
    return $zip_file;

}



/**
 * Publish upgrade 
 *
 * Publishes an upgrade to the appropriate repository 
 *
 * @param string $upgrade_id The ID# from the 'internal_upgrades' of the upgrade to publish.
 */
public function publish(int $upgrade_id)
{ 

    // Get upgrade
    if (!$upgrade = db::get_idrow('internal_upgrades', $upgrade_id)) { 
        throw new UpgradeException('not_exists', $upgrade_id);
    }

    // Get package
    $pkg = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", $upgrade['package']);

    // Get repo
    if (!$repo = db::get_idrow('internal_repos', $pkg['repo_id'])) { 
        throw new RepoException('not_exists', $pkg['repo_id']);
    }

    // Debug
    debug::add(2, tr("Starting to publish upgrade for package {1}, version {2}", $upgrade['package'], $upgrade['version']));

    // Compile upgrade
    $zip_file = $this->compile($upgrade_id);

    // Set request
    $request = array(
        'version' => $upgrade['version'] 
    );

    // Send request to repo
    $network = app::make(network::class);
    $vars = $network->send_repo_request((int) $repo['id'], $upgrade['package'], 'publish_upgrade', $request, false, $zip_file);

    // Update database
    db::query("UPDATE internal_upgrades SET status = 'published' WHERE id = %i", $upgrade_id);
    db::query("UPDATE internal_packages SET version = %s WHERE alias = %s", $upgrade['version'], $upgrade['package']);

    // Publish package
    if ($upgrade['package'] != 'core') { 
        $package = app::make(package::class);
        $package->publish($upgrade['package']);
    }

    // Load package, check for GIthub repo
    $client = app::make(package_config::class, ['pkg_alias' => $upgrade['package']]);
    $pkg = $client->load();
    if (isset($pkg->git_repo_url) && $pkg->git_repo_url != '') { 
        $git = app::make(github::class);
        $git->compare($upgrade['package'], $upgrade['version'] );
        $is_git = 1;
    } else { $is_git = 0; }

    // Debug
    debug::add(1, tr("Successfully published upgrade for package {1}, version {2}", $upgrade['package'], $upgrade['version']));

    // Return
    return $is_git;

}

/**
 * Install upgrades for a specified package 
 *
 * @param string $pkg_alias The alias of the package to upgrade
 *
 * @return string The new vesion the package was upgraded to
 */
public function install(string $pkg_alias)
{ 

    // Get package
    if (!$row = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", $pkg_alias)) { 
        throw new PackageException('not_exists', $pkg_alias);
    }
    $new_version = $row['version'];

    // Set request
    $request = array(
        'version' => $row['version']
    );

    // Debug
    debug::add(3, tr("Contacting repository, and requesting available upgrades for package {1}", $pkg_alias));

    // Send request to repo
    $client = app::make(network::class);
    $response = $client->send_repo_request((int) $row['repo_id'], $pkg_alias, 'download_upgrade', $request);
    if (!isset($response['upgrades'])) { return; }

    // Go through upgrades
    foreach ($response['upgrades'] as $vars) { 

        // Save zip file
        $zip_file = sys_get_temp_dir() . '/apex_upgrade-' . $pkg_alias . '_' . str_replace('.', '_', $vars['version']) . '.zip';
        if (file_exists($zip_file)) { @unlink($zip_file); }
        file_put_contents($zip_file, base64_decode($vars['contents']));

        // Install from zip
        $this->install_from_zip($pkg_alias, $vars['version'], $zip_file);
        $new_version = $vars['version'];
    }

    // Debug
    debug::add(1, tr("Successfully upgraded package {1} to version {2}", $pkg_alias, $new_version));

    // Return
    return $new_version;

}

/**
 * Install single upgrade from zip file 
 *
 * @param string $pkg_alias The alias of the package upgrade is being installed for
 * @param string $version The new version of the zip file
 * @param string $zip_file The location of the zip file
 */
protected function install_from_zip(string $pkg_alias, string $version, string $zip_file)
{ 

    // Debug
    debug::add(3, tr("Starting to upgrade package {1} to version {2} from zip file", $pkg_alias, $version));

    // Unpack zip file
    $upgrade_dir = sys_get_temp_dir() . '/apex_upgrade_' . $pkg_alias . '_' . $version;
    if (is_dir($upgrade_dir)) { io::remove_dir($upgrade_dir); }
    io::unpack_zip_archive($zip_file, $upgrade_dir);

    // Create rollback dir
    $prev_version = db::get_field("SELECT version FROM internal_packages WHERE alias = %s", $pkg_alias);
    $rollback_dir = SITE_PATH . '/etc/' . $pkg_alias . '/rollback/' . $prev_version;
    io::create_blank_dir($rollback_dir);

    // Load upgrade file
    $class_name = "\\apex\\" . 'upgrade_' . $pkg_alias . '_' . str_replace('.', '_', $version);
    require_once("$upgrade_dir/upgrade.php");
    $upgrade = new $class_name();

    // Debug
    debug::add(4, tr("Installing upgrade, loaded upgrade class file, package {1}, version {2}", $pkg_alias, $version));

    // Run install SQL, if needed
    io::execute_sqlfile("$upgrade_dir/install.sql");

    // Execute PHP, if needed
    if (method_exists($upgrade, 'install_before')) { 
        $upgrade->install_before();
    }

    // Copy over files
    list($new_files, $merge_errors) = pkg_component::sync_from_dir($pkg_alias, $upgrade_dir, $rollback_dir);
    file_put_contents("$rollback_dir/added.json", json_encode($new_files));

    // Debug
    debug::add(4, tr("Installing upgrade, copied over all files as necessary for package {1}, version {2}", $pkg_alias, $version));

    // Copy deleted files to rollback dir
    $deleted = json_decode(file_get_contents("$upgrade_dir/deleted.json"), true);
    foreach ($deleted as $file) {

        // Parse filename
        $parts = explode('/', $file);
        $subdir = array_shift($parts);
        if (!isset(pkg_component::$dest_dirs[$subdir])) { continue; }

        // Get filename
        $src_file = SITE_PATH . '/' . pkg_component::$dest_dirs[$subdir] . '/' . implode('/', $parts);
        $src_file = str_replace('~alias~', $pkg_alias, $src_file);
        if (!file_exists(SITE_PATH . '/' . $src_file)) { continue; }

        // Copy file
        io::create_dir(dirname("$rollback_dir/$file"));
        copy(SITE_PATH . '/' . $src_file, "$rollback_dir/$file");
    }

    // Delete components
    foreach ($deleted as $file) {
        pkg_component::remove_from_filename($pkg_alias, $file);
    }

    // Debug
    debug::add(4, tr("Installing upgrade, successfully created / deleted all necessary components for package {1}, version {2}", $pkg_alias, $version));

    // Install package configuration
    $pkg_client = app::make(package_config::class, ['pkg_alias' => $pkg_alias]);
    $pkg_client->install_configuration();

    // Execute PHP, if needed
    if (method_exists($upgrade, 'install_after')) { 
        $upgrade->install_after();
    }

    // Save rollback files
    if (file_exists("$upgrade_dir/rollback.sql")) { 
        copy("$upgrade_dir/rollback.sql", "$rollback_dir/rollback.sql");
    }
    copy("$upgrade_dir/upgrade.php", "$rollback_dir/upgrade.php");

    // Update database
    db::query("UPDATE internal_packages SET version = %s, last_modified = now() WHERE alias = %s", $version, $pkg_alias);

    // Add history to database
    db::insert('internal_upgrades', array(
        'status' => 'installed',
        'package' => $pkg_alias,
        'version' => $version, 
        'prev_version' => $prev_version)
    );

    // Clean up
    io::remove_dir($upgrade_dir);
    if (file_exists($zip_file)) { @unlink($zip_file); }

    // Debug
    debug::add(3, tr("Successfully installed single upgrade for package {1}, version {2}", $pkg_alias, $version));

    // Return
    return true;

}

/**
 * Rollback upgrades 
 *
 * @param string $pkg_alias The alias of the package to rollback
 * @param string $version The version to rollback to
 */
public function rollback(string $pkg_alias, string $version)
{ 

    // Get package
    if (!$row = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", $pkg_alias)) { 
        throw new PackageException('not_exists', $pkg_alias);
    }
    $prev_version = $row['prev_version'];
    $current_version = $row['version'];

    // Debug
    debug::add(1, tr("Starting to rollback package {1} from version {2} to version {3}", $pkg_alias, $current_version, $prev_version), 'info');

    // Go through upgrades
    $rows = db::query("SELECT * FROM internal_upgrades WHERE package = %s AND status = 'installed' ORDER BY id DESC");
    foreach ($rows as $row) { 

        // Check version
        if (!version_compare($prev_version, $row['prev_version'], '>')) { 
            break;
        }

        // Rollback single upgrade
        $this->rollback_single($pkg_alias, $row['prev_version']);
    }

    debug::add(1, tr("Successfully performed rollback to package {1}, from version {2} to version {3}", $pkg_alias, $current_version, $prev_version), 'info');

}

/**
 * Rollback a single upgrade 
 *
 * @param string $pkg_alias The alias of the package to rollback
 * @param string $version The single version number being rolled back
 */
protected function rollback_single(string $pkg_alias, string $version)
{ 

    // Ensure rollback directory exists
    $rollback_dir = SITE_PATH . '/etc/' . $pkg_alias . '/rollback/' . $version;
    if (!is_dir($rollback_dir)) { 
        throw new UpgradeException('no_rollback', $pkg_alias, $version);
    }

    // Debug
    debug::add(3, tr("Starting single rollback for package {1}, version {2}", $pkg_alias, version));

    // Load upgrade file
    $class_name = "\\apex\\" . 'upgrade_' . $pkg_alias . '_' . str_replace('.', '_', $version);
    require_once("$rollback_dir/upgrade.php");
    $upgrade = new $class_name();

    // Sync directories
    pkg_component::sync_from_dir($pkg_alias, $rollback_dir);

    // Delete newly added components
    $added = json_decode(file_get_contents("$rollback_dir/added.json"), true);
    foreach ($added as $file) {
        pkg_component::remove_from_filename($pkg_alias, $file);
    }

    // Execute SQL file
    io::execute_sqlfile("$rollback_dir/rollback.sql");

    // Execute PHP, if needed
    if (method_exists($upgrade, 'rollback')) { 
        $upgrade->rollback();
    }

    // Load package configuration
    $pkg_client = app::make(package_config::class, ['pkg_alias' => $pkg_alias]);
    $pkg_client->install_configuration();

    // Update database
    db::query("UPDATE internal_packages SET version = %s WHERE alias = %s", $version, $pkg_alias);
    db::query("DELETE FROM internal_upgrades WHERE package = %s AND version = %s AND status = 'installed'", $pkg_alias, $version);

    // Debug
    debug::add(3, tr("Completed single rollback on package {1} to version {2}", $pkg_alias, $version));

    // Update database
    db::query("UPDATE internal_packages SET version = %s WHERE alias = %s", $version, $pkg_alias);
    db::query("DELETE FROM internal_upgrades WHERE package = %s AND status = 'installed' AND prev_version = %s", $pkg_alias, $version);

    // Clean up
    io::remove_dir($rollback_dir);


}


}

