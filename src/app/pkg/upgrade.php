<?php
declare(strict_types = 1);

namespace apex\app\pkg;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\app\sys\network;
use apex\svc\io;
use apex\svc\components;
use apex\app\exceptions\PackageException;
use apex\app\exceptions\RepoException;
use apex\app\exceptions\UpgradeException;
use apex\app\pkg\package;
use apex\app\pkg\package_config;
use apex\app\pkg\pkg_component;
use CurlFile;


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
    $conf = base64_decode('PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4OwoKdXNlIGFwZXhcREI7CnVzZSBhcGV4XHJlZ2lzdHJ5Owp1c2UgYXBleFxsb2c7CnVzZSBhcGV4XGRlYnVnOwoKY2xhc3MgdXBncmFkZV9+cGFja2FnZX5ffnZlcnNpb25+CnsKCi8qKgoqIEluc3RhbGwgYmVmb3JlLiAgVGhpcyBjb2RlIGlzIGV4ZWN1dGVkIGJlZm9yZSBhbnkgZmlsZXMgLyBjb21wb25lbnRzCiogYXJlIHVwZGF0ZWQuCiovCnB1YmxpYyBmdW5jdGlvbiBpbnN0YWxsX2JlZm9yZSgpCnsKCgp9CgovKioKKiBJbnN0YWxsIGFmdGVyLiAgVGhpcyBjb2RlIGlzIGV4ZWN1dGVkIGFmdGVyIGFsbCBmaWxlcyAvIGNvbXBvbmVudHMgCiogaGF2ZSBiZWVuIHVwZ3JhZGVkLgoqLwpwdWJsaWMgZnVuY3Rpb24gaW5zdGFsbF9hZnRlcigpCnsKCgp9CgovKioKKiBST2xsYmFjay4gIFRoaXMgY29kZSBpcyBleGVjdXRlZCB1cG9uIHRoZSB1cGdyYWRlIGJlaW5nIHJvbGxlZCBiYWNrLgoqLwpwdWJsaWMgZnVuY3Rpb24gcm9sbGJhY2soKQp7Cgp9Cgp9Cgo=');
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

    // Load package
    $pkg_client = new package_config($package);
    $pkg = $pkg_client->load();

    // Debug
    debug::add(4, tr("Starting to create file hash for upgrade for package, {1}", $package));

    // Delete existing hashes
    db::query("DELETE FROM internal_file_hashes WHERE upgrade_id = %i", $upgrade_id);

    // Go through all components
    $comps = array();
    $rows = db::query("SELECT * FROM internal_components WHERE owner = %s ORDER BY id", $package);
    foreach ($rows as $row) { 

        $files = components::get_all_files($row['type'], $row['alias'], $row['package'], $row['parent']);
        foreach ($files as $file) { 
            $this->add_single_file_hash($upgrade_id, $file);
        }

        // Add to $components array
        $vars = array(
            'type' => $row['type'],
            'order_num' => $row['order_num'],
            'package' => $row['package'],
            'parent' => $row['parent'],
            'alias' => $row['alias'],
            'value' => $row['value']
        );
        array_push($comps, $vars);
    }

    // Go through external files
    foreach ($pkg->ext_files as $file) { 
        $this->add_single_file_hash($upgrade_id, $file);
    }

    // Save components.json file
    file_put_contents("$this->upgrade_dir/components.json", json_encode($comps));

    // Debug
    debug::add(3, tr("Successfully compiled file hash for upgrade point of package, {1}", $package));

}

/**
 * Add single file hash 
 *
 * @param int $upgrade_id The ID# of the upgrade
 * @param string $file The file to add, relative to the installation directory
 */
protected function add_single_file_hash(int $upgrade_id, string $file)
{ 

    // Get SHA1 hash
    if (!file_exists(SITE_PATH . '/' . $file)) { return; }
    $file_hash = sha1(file_get_contents(SITE_PATH . '/' . $file));

    // Add to database
    db::insert('internal_file_hashes', array(
        'is_system' => 0,
        'upgrade_id' => $upgrade_id,
        'filename' => $file,
        'file_hash' => $file_hash)
    );

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

    // Load package
    $pkg_client = new package_config($upgrade['package']);
    $pkg = $pkg_client->load();

    // Create tmp directory
        $this->upgrade_dir = sys_get_temp_dir() . '/apex_upgrade_' . $upgrade['package'];
    if (is_dir($this->upgrade_dir)) { io::remove_dir($this->upgrade_dir); }
    io::create_dir($this->upgrade_dir);
    io::create_dir("$this->upgrade_dir/files");
    io::create_dir("$this->upgrade_dir/pkg");

    // Get current file hash
    $this->file_hash = db::get_hash("SELECT filename,file_hash FROM internal_file_hashes WHERE upgrade_id = %s", $upgrade_id);

    // GO through all components
    $done = array();
    $rows = db::query("SELECT * FROM internal_components WHERE owner = %s", $upgrade['package']);
    foreach ($rows as $row) { 
        $this->compile_component($row);
        $done[] = implode(":", array($row['type'], $row['package'], $row['parent'], $row['alias']));
    }

    // Go through external files
    foreach ($pkg->ext_files as $file) { 
        $this->compile_single_file($file);
    }

    // Go through documentation
    $docs_dirs = $upgrade['package'] == 'core' ? array('', 'components', 'core', 'training', 'user_manual') : array($upgrade['package']);
    foreach ($docs_dirs as $docdir) { 
        if (!is_dir(SITE_PATH . "/docs/$docdir")) { continue; }
        $docs_files = io::parse_dir(SITE_PATH . "/docs/$docdir", false);
        foreach ($docs_files as $doc_file) { 
            $this->compile_single_file("docs/$docdir/$doc_file");
        }
    }

    // Save components.json file
    file_put_contents("$this->upgrade_dir/toc.json", json_encode($this->toc));
    file_put_contents("$this->upgrade_dir/components.json", json_encode($this->components));

    // Debug
    debug::add(5, tr("Compiling upgrade, went through all existing components, package {1}, version {2}", $upgrade['package'], $upgrade['version']));

    // Get previous components
    $etc_dir = SITE_PATH . '/etc/' . $upgrade['package'] . '/upgrades/' . $upgrade['version'];
    $prev_components = json_decode(file_get_contents("$etc_dir/components.json"), true);

    // Get deleted components
    $deleted = array();
    foreach ($prev_components as $vars) { 
        $chk = implode(":", array($vars['type'], $vars['package'], $vars['parent'], $vars['alias']));
        if (in_array($chk, $done)) { continue; }
        $deleted[] = $vars;
    }
    file_put_contents("$this->upgrade_dir/deleted.json", json_encode($deleted));

    // Copy over basic upgrade files
    $files = array('upgrade.php', 'install.sql', 'rollback.sql');
    foreach ($files as $file) { 
        if (!file_exists($etc_dir . '/' . $file)) { continue; }
        copy($etc_dir . '/' . $file, "$this->upgrade_dir/$file");
    }

    // Copy package files
    $pkg_dir = SITE_PATH . '/etc/' . $upgrade['package'];
    $pkg_files = array('package.php', 'install.sql', 'install_after.sql', 'reset.sql', 'remove.sql');
    foreach ($pkg_files as $file) { 
        if (!file_exists("$pkg_dir/$file")) { continue; }

        // Update version if package.php file of core package
        if ($upgrade['package'] == 'core' && $file == 'package.php') { 
            $text = str_replace("~version~", $upgrade['version'], file_get_contents(SITE_PATH . '/etc/core/package.php'));
            file_put_contents("$this->upgrade_dir/pkg/package.php", $text);
            continue;
        }

        // Copy if not package.php of core package
        copy("$pkg_dir/$file", "$this->upgrade_dir/pkg/$file");



    }

    // Archive file
    $zip_file = 'apex_upgrade_' . $upgrade['package'] . '-' . str_replace(".", "_", $upgrade['version']) . ".zip";
    $archive_file = sys_get_temp_dir() . '/' . $zip_file;
    io::create_zip_archive($this->upgrade_dir, $archive_file);

    // Clean up
    io::remove_dir($this->upgrade_dir);

    // Debug
    debug::add(4, tr("Successfully compiled upgrade, and archived it at {1}", $archive_file));

    // Return
    return $zip_file;

}

/**
 * Compile single component 
 *
 * Compile a single component.  Will get the .php file of component, and check 
 * to see whether or not it is updated or added, and process accordingly. 
 *
 * @param array $row The database row from 'internal_components' table
 */
protected function compile_component(array $row)
{ 

    // Debug
    debug::add(5, tr("Compiling single component for upgrade, type: {1}, package: {2}, parent: {3}, alias: {4}", $row['type'], $row['package'], $row['parent'], $row['alias']));

    // Get PHP file
    $php_file = components::get_file($row['type'], $row['alias'], $row['package'], $row['parent']);
    if ($php_file == '' || !file_exists(SITE_PATH . '/' . $php_file)) { 
        return;
    }

    // Set vars
    $vars = array(
        'order_num' => $row['order_num'],
        'type' => $row['type'],
        'package' => $row['package'],
        'parent' => $row['parent'],
        'alias' => $row['alias'],
        'value' => $row['value']
    );

    // Get SHA1 hashes
    $chk_hash = sha1(file_get_contents(SITE_PATH . '/' . $php_file));
    $cur_hash = $this->file_hash[$php_file] ?? '';

// Check hashes
    if ($cur_hash == '' ?? $cur_hash != $chk_hash) { 
        $this->components[] = $vars;
    }

    // Go through all files
    $files = components::get_all_files($row['type'], $row['alias'], $row['package'], $row['parent']);
    foreach ($files as $file) { 
        $this->compile_single_file($file);
    }

}

/**
 * Compile single file 
 *
 * Compile a single file.  Checks the current SHA1 hash against the one stroed 
 * upon upgrade point creation, and adds file to upgrade if needed. 
 *
 * @param string $file Filename to check, relative to installation directory.
 */
protected function compile_single_file(string $file)
{ 

    // Check file exists
    if (!file_exists(SITE_PATH . '/' . $file)) { 
        return;
    }

    // Get hashes
    $chk_hash = sha1(file_get_contents(SITE_PATH . '/' . $file));
    $cur_hash = $this->file_hash[$file] ?? '';

    // Check hash
    if ($cur_hash == '' || $cur_hash != $chk_hash) { 
        copy(SITE_PATH . '/' . $file, $this->upgrade_dir . '/files/' . $this->file_num);
        $this->toc[$file] = $this->file_num;
        $this->file_num++;
    }

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
    if (!$pkg = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", $upgrade['package'])) { 
        throw new PackageException('not_exists', $upgrade['package']);
    }

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
        'version' => $upgrade['version'], 
        'contents' => new CurlFile(sys_get_temp_dir() . '/' . $zip_file, 'application/gzip', $zip_file)
    );

    // Send request to repo
    $network = app::make(network::class);
    $vars = $network->send_repo_request((int) $repo['id'], $upgrade['package'], 'publish_upgrade', $request, false, $zip_file);

    // Update database
    db::query("UPDATE internal_upgrades SET status = 'published' WHERE id = %i", $upgrade_id);
    db::query("UPDATE internal_packages SET version = %s WHERE alias = %s", $upgrade['version'], $upgrade['package']);

    // Publish package
    if ($upgrade['package'] != 'core') { 
        $package = new package();
        $package->publish($upgrade['package']);
    }

    // Debug
    debug::add(1, tr("Successfully published upgrade for package {1}, version {2}", $upgrade['package'], $upgrade['version']));

    // Return
    return true;

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
    $this->upgrade_dir = sys_get_temp_dir() . '/apex_upgrade_' . $pkg_alias . '_' . $version;
    if (is_dir($this->upgrade_dir)) { io::remove_dir($this->upgrade_dir); }
    io::unpack_zip_archive($zip_file, $this->upgrade_dir);

    // Create rollback dir
    $rollback_dir = SITE_PATH . '/etc/' . $pkg_alias . '/rollback/' . $version;
    if (is_dir($rollback_dir)) { io::remove_dir($rollback_dir); }
    io::create_dir($rollback_dir);
    io::create_dir("$rollback_dir/files");
    io::create_dir("$rollback_dir/pkg");

    // Load upgrade file
    $class_name = "\\apex\\" . 'upgrade_' . $pkg_alias . '_' . str_replace('.', '_', $version);
    require_once("$this->upgrade_dir/upgrade.php");
    $upgrade = new $class_name();

    // Debug
    debug::add(4, tr("Installing upgrade, loaded upgrade class file, package {1}, version {2}", $pkg_alias, $version));

    // Run install SQL, if needed
    io::execute_sqlfile("$this->upgrade_dir/install.sql");
 

    // Execute PHP, if needed
    if (method_exists($upgrade, 'install_before')) { 
        $upgrade->install_before();
    }

    // Set variables
    $new_toc = array(
    'files' => array(),
    'delete' => array(),
    'add' => array()
    );
    $new_num = 1;

    // Copy over files
    $toc = json_decode(file_get_contents("$this->upgrade_dir/toc.json"), true);
    foreach ($toc as $file => $file_num) { 

        // Add to new TOC
        if (file_exists(SITE_PATH . '/' . $file)) { 
            copy(SITE_PATH . '/' . $file, "$rollback_dir/files/$new_num");
            $new_toc['files'][$file] = $new_num;
            $new_num++;
        } else { 
            array_push($new_toc['delete'], $file);
        }

        // Save file
        if (!(file_exists(SITE_PATH . '/' . $file) && preg_match("/views\/tpl\/public\//", $file))) { 
            if (file_exists(SITE_PATH . '/' . $file)) { @unlink(SITE_PATH . '/' . $file); }
            io::create_dir(dirname(SITE_PATH . '/' . $file));
            if (!file_exists("$this->upgrade_dir/files/$file_num")) { 
                file_put_contents(SITE_PATH . '/' . $file, '');
            } else { 
                copy("$this->upgrade_dir/files/$file_num", SITE_PATH . '/' . $file);
            }
        }
    }

    // Debug
    debug::add(4, tr("Installing upgrade, copied over all files as necessary for package {1}, version {2}", $pkg_alias, $version));

    // Go through components
    $components = json_decode(file_get_contents("$this->upgrade_dir/components.json"), true);
    foreach ($components as $row) { 
        if ($row['type'] == 'view') { $comp_alias = $row['alias']; }
        else { $comp_alias = $row['parent'] == '' ? $row['package'] . ':' . $row['alias'] : $row['package'] . ':' . $row['parent'] . ':' . $row['alias']; }

        pkg_component::add($row['type'], $comp_alias, $row['value'], (int) $row['order_num'], $pkg_alias);
    }

    // Delete needed components
    $deleted = json_decode(file_get_contents("$this->upgrade_dir/deleted.json"), true);
    foreach ($deleted as $row) { 

        // Get files
        $files = components::get_all_files($row['type'], $row['alias'], $row['package'], $row['parent']);
        foreach ($files as $file) { 
            if (!file_exists(SITE_PATH . '/' . $file)) { continue; }
            copy(SITE_PATH . '/' .  $file, "$rollback_dir/files/$new_num");
            $new_toc['files'][$file] = $new_num;
            $new_num++;
        }
        array_push($new_toc['add'], $row);

        // Delete component
        $comp_alias = $row['parent'] == '' ? $pkg_alias . ':' . $row['alias'] : $pkg_alias . ':' . $row['parent'] . ':' . $row['alias'];
        pkg_component::remove($row['type'], $comp_alias);
    }

    // Debug
    debug::add(4, tr("Installing upgrade, successfully created / deleted all necessary components for package {1}, version {2}", $pkg_alias, $version));

    // Copy over new package files
    $etc_dir = SITE_PATH . '/etc/' . $pkg_alias;
    $files = array('package.php', 'install.sql', 'install_after.sql', 'reset.sql', 'remove.sql', 'components.json');
    foreach ($files as $file) { 

        // Copy to rollback, if needed
        if (file_exists("$etc_dir/$file")) { 
            copy("$etc_dir/$file", "$rollback_dir/pkg/$file");
            @unlink("$etc_dir/$file");
        }

        // Copy package file
        if (file_exists("$this->upgrade_dir/pkg/$file")) { 
            copy("$this->upgrade_dir/pkg/$file", "$etc_dir/$file");
        }
    }

    // Install package configuration
    $pkg_client = new package_config($pkg_alias);
    $pkg_client->install_configuration();

    // Execute PHP, if needed
    if (method_exists($upgrade, 'install_after')) { 
        $upgrade->install_after();
    }

    // Save rollback files
    file_put_contents("$rollback_dir/changes.json", json_encode($new_toc));
    if (file_exists("$this->upgrade_dir/rollback.sql")) { 
        copy("$this->upgrade_dir/rollback.sql", "$rollback_dir/rollback.sql");
    }

    // Update database
    db::query("UPDATE internal_packages SET version = %s, last_modified = now() WHERE alias = %s", $version, $pkg_alias);

    // Add history to database
    db::insert('internal_upgrades', array(
        'status' => 'installed',
        'package' => $pkg_alias,
        'version' => $version)
    );

    // Clean up
    io::remove_dir($this->upgrade_dir);
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
        if (!version_compare($prev_version, $row['version'], '>')) { 
            break;
        }

        // Rollback single upgrade
        $this->rollback_single($pkg_alias, $row['version']);
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

    // Get changes
    $toc = json_decode(file_get_contents("$rollback_dir/changes.json"), true);

    // Go through all files
    foreach ($toc['files'] as $file => $file_num) { 
        if (file_exists(SITE_PATH . '/' . $file)) { @unlink(SITE_PATH . '/' . $file); }
        copy("$rollback_dir/files/$file_num", SITE_PATH . '/' . $file);
    }

    // Delete necessary components
    foreach ($toc['delete'] as $row) { 
        $comp_alias = $row['parent'] == '' ? $pkg_alias . ':' . $row['alias'] : $pkg_alias . ':' . $row['parent'] . ':' . $row['alias'];
        pkg_component::remove($row['type'], $comp_alias);
    }

    // Add components
    foreach ($toc['add'] as $row) { 
        $comp_alias = $row['parent'] == '' ? $pkg_alias . ':' . $row['alias'] : $pkg_alias . ':' . $row['parent'] . ':' . $row['alias'];
        pkg_component::add($row['type'], $comp_alias, $row['value'], $row['order_num']);
    }

    // Copy over package files
    $etc_dir = SITE_PATH . '/etc/' . $pkg_alias;
    $files = array('package.php', 'install.sql', 'install_after.sql', 'reset.sql', 'remove.sql', 'components.json');
    foreach ($files as $file) { 
        if (file_exists("$etc_dir/$file")) { @unlink("$etc_dir/$file"); }
        if (file_exists("$rollback_dir/pkg/$file")) { 
            copy("$rollback_dir/pkg/$file", "$etc_dir/$file");
        }
    }

    // Load package configuration
    $pkg_client = new package_config($pkg_alias);

    $pkg_client->install_configuration();
    // Update database
    db::query("UPDATE internal_packages SET version = %s WHERE alias = %s", $version, $pkg_alias);
    db::query("DELETE FROM internal_upgrades WHERE package = %s AND version = %s AND status = 'installed'", $pkg_alias, $version);

    // Debug
    debug::add(3, tr("Completed single rollback on package {1} to version {2}", $pkg_alias, $version));

}


}

