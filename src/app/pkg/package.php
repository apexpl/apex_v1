<?php
declare(strict_types = 1);

namespace apex\app\pkg;

use apex\app;
use apex\libc\{db, debug, io, components, msg};
use apex\app\sys\network;
use apex\app\pkg\{package_config, github};
use apex\app\msg\objects\event_message;
use apex\app\exceptions\{ApexException, PackageException, RepoException};


/**
 * Handles all package functions -- create, compile, download, install, 
 * remove. 
 */
class package
{

    // Properties
    private $pkg_alias;


/**
 * Insert a new package into the database 
 *
 * @param int $repo_id The ID# of the repo the package belongs to
 * @param string $pkg_alias The alias of the package
 * @param string $name The full name of the package
 * @param string $access Access level of the package (public / private), defaults to 'public'
 * @param string $version The version of the package, defaults to 1.0.0
 */
public function insert(int $repo_id, string $pkg_alias, string $name, string $access = 'public', string $version = '1.0.0')
{ 

    // Validate the alias
    if (!$this->validate_alias($pkg_alias)) { 
        throw new PackageException('invalid_alias', $pkg_alias);
    }

    // Debug
    debug::add(2, tr("Inserting new package into database, alias: {1}, name: {2}, repo_id: {3}", $pkg_alias, $name, $repo_id), 'info');

    // Insert into db
    db::insert('internal_packages', array(
        'access' => $access,
        'repo_id' => $repo_id,
        'version' => $version,
        'alias' => strtolower($pkg_alias),
        'name' => $name)
    );
    $package_id = db::insert_id();

    // Return
    return $package_id;

}

/**
 * Create a new package for development. 
 *
 * @param int $repo_id The ID# of the repo the package belongs to
 * @param string $pkg_alias The alias of the package
 * @param string $name The full name of the package
 * @param string $access Access level of the package (public / private), defaults to 'public'
 * @param string $version The version of the package, defaults to 1.0.0
 *
 * @return int The ID# of the newly created package
 */
public function create(int $repo_id, string $pkg_alias, string $name, string $access = 'public', string $version = '1.0.0')
{ 

    // Debug
    debug::add(5, tr("Starting creation of package alias: {1}, name: {2}, repo_id: {3}", $pkg_alias, $name, $repo_id));

    // Insert package to database
    $package_id = $this->insert($repo_id, $pkg_alias, $name, $access, $version);
    $pkg_alias = strtolower($pkg_alias);

    // Create directories
    $pkg_dir = SITE_PATH . '/etc/' . $pkg_alias;
    io::create_dir($pkg_dir);
    io::create_dir("$pkg_dir/upgrades");
    io::create_dir(SITE_PATH . '/src/' . $pkg_alias);

    // Save blank files
    file_put_contents("$pkg_dir/install.sql", '');
    file_put_contents("$pkg_dir/reset.sql", '');
    file_put_contents("$pkg_dir/remove.sql", '');

    // Save package.php file
    $pkg_file = base64_decode('PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4OwoKdXNlIGFwZXhcYXBwOwp1c2UgYXBleFxsaWJjXGRiOwp1c2UgYXBleFxsaWJjXHJlZGlzOwoKCi8qKgogKiBUaGUgcGFja2FnZSBjb25maWd1cmF0aW9uLCBpbmNsdWRpbmcgdmFyaW91cyBwcm9wZXJ0aWVzLCAKICogYW5kIFBIUCBjb2RlIHRvIGJlIGV4ZWN1dGVkIGR1cmluZyBpbnN0YWxsLCByZXNldCwgYW5kIAogKiByZW1vdmFsIG9mIHRoZSBwYWNrYWdlLgogKi8KY2xhc3MgcGtnX35hbGlhc34gCnsKCiAgICAvKioKICAgICAqIEJhc2ljIHBhY2thZ2UgdmFyaWFibGVzLiAgVGhlICRhcmVhIGNhbiBiZSBlaXRoZXIgJ3ByaXZhdGUnLCAKICAgICAqICdjb21tZXJjaWFsJywgb3IgJ3B1YmxpYycuICBJZiBzZXQgdG8gJ3ByaXZhdGUnLCBpdCB3aWxsIG5vdCBhcHBlYXIgb24gdGhlIHB1YmxpYyByZXBvc2l0b3J5IGF0IGFsbCwgYW5kIAogICAgICogaWYgc2V0IHRvICdjb21tZXJjaWFsJywgeW91IG1heSBkZWZpbmUgYSBwcmljZSB0byBjaGFyZ2Ugd2l0aGluIHRoZSAkcHJpY2UgdmFyaWFibGUgYmVsb3cuCiAgICAgKi8KICAgIHB1YmxpYyAkYWNjZXNzID0gJ35hY2Nlc3N+JzsKICAgIHB1YmxpYyAkcHJpY2UgPSAwOwogICAgcHVibGljICRuYW1lID0gJ35uYW1lfic7CiAgICBwdWJsaWMgJGRlc2NyaXB0aW9uID0gJyc7CgogICAgLyoqCiAgICAgKiBHaXRodWIgLyBnaXQgU2VydmljZSBSZXBvc2l0b3J5IHZhcmlhYmxlcwogICAgICoKICAgICAqIE9ubHkgcmVxdWlyZWQgaWYgeW91IHdpc2ggdG8gdXNlIHRoZSBidWlsdC1pbiBHaXQgaW50ZWdyYXRpb24gd2l0aGluIEFwZXguICBUaGVzZSAKICAgICAqIHZhcmlhYmxlcyBhbGxvdyB5b3UgdG8gZGVmaW5lIHRoZSBVUkwgb2YgdGhlIGdpdCByZXBvc2l0b3J5LCBicmFuY2ggbmFtZSwgYW5kIGlmIAogICAgICogdGhpcyBpcyBhIGZvcmtlZCByZXBvc2l0b3J5LCBhbHNvIHRoZSB1cHN0cmVhbSByZXBvIFVSTC4KICAgICAqCiAgICAgKiBPbmNlIGRldmVsb3BtZW50IG9mIHRoZSBwYWNrYWdlIGlzIGNvbXBsZXRlLCB5b3UgbWF5IGluaXRpYWxpemUgdGhlIGdpdCAKICAgICAqIHJlcG9zaXRvcnkgb2YgdGhlIHBhY2thZ2UgYW5kIGNvbW1pdCBpdCB3aXRoOgogICAgICogICAgIHBocCBhcGV4LnBocCBnaXRfaW5pdCB+YWxpYXN+CiAgICAgKiAKICAgICAqIEZvciBmdWxsIGluZm9ybWF0aW9uIG9uIGdpdCBpbnRlZ3JhdGlvbiwgcGxlYXNlIHJlZmVyIHRvIHRoZSBkb2N1bWVudGF0aW9uIGF0OgogICAgICogICAgIGh0dHBzOi8vYXBleC1wbGF0Zm9ybS5vcmcvZG9jcy9naXRodWIKICAgICAqLwogICAgcHVibGljICRnaXRfcmVwb191cmwgPSAnJzsKICAgIHB1YmxpYyAkZ2l0X3Vwc3RyZWFtX3VybCA9ICcnOwogICAgcHVibGljICRnaXRfYnJhbmNoX25hbWUgPSAnbWFzdGVyJzsKCgovKioKICogRGVmaW5lIHBhY2thZ2UgY29uZmlndXJhdGlvbi4KICoKICogVGhlIGNvbnN0cnVjdG9yIHRoYXQgZGVmaW5lcyB0aGUgdmFyaW91cyBjb25maWd1cmF0aW9uIAogKiBhcnJheXMgb2YgdGhlIHBhY2thZ2Ugc3VjaCBhcyBjb25maWcgdmFycywgaGFzaGVzLCBtZW51cywgZXh0ZXJuYWwgZmlsZXMsIGFuZCBzbyBvbi4KICoKICogRm9yIGZ1bGwgZGV0YWlscyBvbiB0aGlzIGZpbGUsIHBsZWFzZSB2aXNpdCB0aGUgZG9jdW1lbnRhdGlvbiBhdDoKICogICAgIGh0dHBzOi8vYXBleC1wbGF0Zm9ybS5vcmcvZG9jcy9wYWNrYWdlX2NvbmZpZwogKi8KcHVibGljIGZ1bmN0aW9uIF9fY29uc3RydWN0KCkgCnsKCiAgICAvKioKICAgICAqIENvbmZpZyBWYXJpYWJsZXMKICAgICAqIAogICAgICogICAgIEFycmF5IG9mIGtleS12YWx1ZSBwYWlycyBmb3IgYWRtaW4gL3N5c3RlbSAKICAgICAqICAgICBkZWZpbmVkIHNldHRpbmdzLCBhdmFpbGFibGUgdmlhIHRoZSBhcHA6Ol9jb25maWcoJGtleSkgbWV0aG9kLgogICAgICovCiAgICAkdGhpcy0+Y29uZmlnID0gYXJyYXkoKTsKCgogICAgLyoqCiAgICAgKiBIYXNoZXMKICAgICAqIAogICAgICogICAgIEFycmF5IG9mIGFzc29jaWF0aXZlIGFycmF5cyB0aGF0IGRlZmluZSB2YXJpb3VzIGxpc3RzIG9mIGtleS12YWx1ZSBwYWlycyB1c2VkLgogICAgICogICAgIFVzZWQgZm9yIHF1aWNrbHkgZ2VuZXJhdGluZyBzZWxlY3QgLyBjaGVja2JveCAvIHJhZGlvIGxpc3RzIHZpYSAKICAgICAqIGhhc2hlczo6Y3JlYXRlX29wdGlvbnMgLyBoYXNoZXM6Z2V0X2hhc2hfdmFyKCkgbWV0aG9kcy4KICAgICAqLwogICAgJHRoaXMtPmhhc2ggPSBhcnJheSgpOwoKCiAgICAvKioKICAgICAqIE1lbnVzCiAgICAgKgogICAgICogICAgIE1lbnVzIGZvciB0aGUgYWRtaW5pc3RyYXRpb24gcGFuZWwsIG1lbWJlcidzIGFyZWEsIAogICAgICogICAgICBhbmQgcHVibGljIHNpdGUuICBQbGVhc2UgcmVmZXIgdG8gZGV2ZWxvcGVyIGRvY3VtZW50YXRpb24gZm9yIGZ1bGwgZGV0YWlscy4KICAgICAqLwogICAgJHRoaXMtPm1lbnVzID0gYXJyYXkoKTsKCgogICAgLyoqCiAgICAgKiBFeHRlcm5hbCBGaWxlcwogICAgICoKICAgICAqICAgICBPbmUgZGltZW5zaW9uYWwgYXJyYXkgb2YgYWxsIGV4dGVybmFsIGZpbGVzLCByZWxhdGl2ZSB0byB0aGUgaW5zdGFsbGF0aW9uIGRpcmVjdG9yeS4KICAgICAqICAgICAoZWcuICcvcGx1Z2lucy9zb21lZGlyL215X3BsdWdpbi5qcycpCiAgICAgKi8KICAgICR0aGlzLT5leHRfZmlsZXMgPSBhcnJheSgpOwoKCiAgICAvKioKICAgICAqIFBsYWNlaG9sZGVycwogICAgICoKICAgICAqIEFzc29jaWF0aXZlIGFycmF5cywga2V5cyBiZWluZyBhIFVSSSwgYW5kIHZhbHVlcyBiZWluZyBhbiBhcnJheSBvZiBwbGFjZWhvbGRlciAKICAgICAqIGFsaWFzZXMuICBWYWx1ZXMgb2YgZWFjaCBwbGFjZWhvbGRlciBjYW4gYmUgZGVmaW5lZCB3aXRoaW4gdGhlIAogICAgICogYWRtaW5pc3RyYXRpb24gcGFuZWwsIGFuZCBwbGFjZWQgd2l0aGluIHBhZ2VzIGluIHRoZSBwdWJsaWMgCiAgICAgKiBzaXRlIC8gbWVtYmVyJ3MgYXJlYSB3aXRoIGEgdGFnIHN1Y2ggYXM6CiAgICAgKgogICAgICogICAgIDxhOnBsYWNlaG9sZGVyIGFsaWFzKyJBTElBUyI+CiAgICAgKgogICAgICogVGhpcyBhbGxvd3MgeW91IGFzIHRoZSBkZXZlbG9wZXIgdG8gaW5jbHVkZSB2aWV3cyB3aXRoaW4gdXBncmFkZXMsIGFuZCAKICAgICAqIHRoZSBhZG1pbmlzdHJhdG9yIHRvIHJldGFpbiB0aGUgdGV4dHVhbCBtb2RpZmljYXRpb25zIHdpdGhvdXQgdGhlbSBiZWluZyBvdmVyd3JpdHRlbiAKICAgICAqIGR1ZSB0byBhbiB1cGdyYWRlLgogICAgICovCiAgICAkdGhpcy0+cGxhY2Vob2xkZXJzID0gYXJyYXkoKTsKCgogICAgLyoqCiAgICAgKiBCb3hsaXN0cwogICAgICogCiAgICAgKiBVc2VkIG1haW5seSB0byBvcmdhbml6ZSBzZXR0aW5ncyBwYWdlcywgYW5kIGV4YW1wbGUgaXMgCiAgICAgKiB0aGUgU2V0dGluZ3MtPlVzZXJzIG1lbnUgb2YgdGhlIGFkbWluaXN0cmF0aW9uIHBhbmVsLiAgVGhpcyBhcnJheSAKICAgICAqIGFsbG93cyB5b3UgdG8gYWRkIGl0ZW1zIHRvIGFuIGV4aXN0aW5nIGJveGxpc3QsIG9yIAogICAgICogY3JlYXRlIHlvdXIgb3duLiAgUGxlYXNlIHJlZmVyIHRvIGRldmVsb3BlciAKICAgICAqIGRvY3VtZW50YXRpb24gZm9yIGZ1bGwgZGV0YWlscy4KICAgICAqLwogICAgJHRoaXMtPmJveGxpc3RzID0gYXJyYXkoKTsKCgogICAgLyoqCiAgICAgKiBEZWZhdWx0IG5vdGlmaWNhdGlvbnMuCiAgICAgKgogICAgICogQW55IGRlZmF1bHQgZS1tYWlsIG5vdGlmaWNhdGlvbnMgeW91IHdvdWxkIGxpa2UgYXV0b21hdGljYWxseSBjcmVhdGVkIAogICAgICogZHVyaW5nIGluc3RhbGxhdGlvbiBvZiB0aGUgcGFja2FnZS4gIHBsZWFzZSByZWZlciAKICAgICAqIHRvIHRoZSBkZXZlbG9wZXIgZG9jdW1lbnRhdGlvbiBmb3IgZnVsbCBkZXRhaWxzIG9mIHRoaXMgYXJyYXkuCiAgICAgKi8KICAgICR0aGlzLT5ub3RpZmljYXRpb25zID0gYXJyYXkoKTsKCgp9CgovKioKICogSW5zdGFsbCBCZWZvcmUKICoKICogICAgICBFeGVjdXRlZCBiZWZvcmV0aGUgaW5zdGFsbGF0aW9uIG9mIGEgcGFja2FnZSBiZWdpbnMuCiAqLwpwdWJsaWMgZnVuY3Rpb24gaW5zdGFsbF9iZWZvcmUoKQp7Cgp9CgovKioKICogSW5zdGFsbCBBZnRlcgogKgogKiAgICAgRXhlY3V0ZWQgYWZ0ZXIgaW5zdGFsbGF0aW9uIG9mIGEgcGFja2FnZSwgb25jZSBhbGwgU1FMIGlzIGV4ZWN1dGVkIGFuZCBjb21wb25lbnRzIGFyZSBpbnN0YWxsZWQuCiAqLwpwdWJsaWMgZnVuY3Rpb24gaW5zdGFsbF9hZnRlcigpIAp7Cgp9CgovKioKICogUmVzZXQKICoKICogICAgICBFeGVjdXRlZCB3aGVuIGFkbWluIHJlc2V0cyB0aGUgcGFja2FnZSB0byBkZWZhdWx0IHN0YXRlIGFmdGVyIGl0IHdhcyBpbnN0YWxsZWQuCiAqLwpwdWJsaWMgZnVuY3Rpb24gcmVzZXQoKSAKewoKfQoKLyoqCiAqIFJlc2V0IHJlZGlzLiAgCiAqIAogKiBJcyBleGVjdXRlZCB3aGVuIGFkbWluaXN0cmF0b3IgcmVzZXRzIHRoZSByZWRpcyBkYXRhYmFzZSwgCiAqIGFuZCBzaG91bGQgcmVnZW5lcmF0ZSBhbGwgcmVkaXMga2V5cyBhcyBuZWNlc3NhcnkgZnJvbSB0aGUgbXlTUUwgZGF0YWJhc2UKICovCnB1YmxpYyBmdW5jdGlvbiByZXNldF9yZWRpcygpCnsKCn0KCi8qKgogKiBSZW1vdmUKICoKICogICAgICBFeGVjdXRlZCB3aGVuIHRoZSBwYWNrYWdlIGlzIHJlbW92ZWQgZnJvbSB0aGUgc2VydmVyLgogKi8KcHVibGljIGZ1bmN0aW9uIHJlbW92ZSgpIAp7Cgp9CgoKfQoK');
    $pkg_file = str_replace("~alias~", $pkg_alias, $pkg_file);
    $pkg_file = str_replace("~name~", $name, $pkg_file);
    $pkg_file = str_replace("~access~", $access, $pkg_file);
    file_put_contents("$pkg_dir/package.php", $pkg_file);

    // Debug
    debug::add(1, tr("Successfully created new package with alias: {1}, name: {2}, repo_id: {3}", $pkg_alias, $name, $repo_id), 'info');

    // Return
    return $package_id;

}

/**
 * Validate a package alias for proper format, and ensure it does not already 
 * exist in the system. 
 *
 * @param string $pkg_alias The package alias to validate
 *
 * @return bool Whether or not the alias is valid
 */
public function validate_alias(string $pkg_alias):bool
{ 

    // Debug
    debug::add(5, tr("Validating package alias: {1}", $pkg_alias));

    // Ensure valid alias
    if ($pkg_alias == '') { return false; }
    elseif (preg_match("/[\W\s]/", $pkg_alias)) { return false; }
    elseif (in_array($pkg_alias, array('app','core','libc'))) { return false; }

    // Check if package already exists
    if ($row = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", strtolower($pkg_alias))) { 
        return false;
    }

    // Return
    return true;

}

/**
 * Compile package 
 *
 * Gathers all files and components on the package, and 
 * compiles as necessary to ready for publication to repository.
 *
 * @param string $pkg_alias The alias of the package to compile.
 *
 * @return string The location of the temporary directory that holds the compiled package.
 */
public function compile(string $pkg_alias):string
{ 

    // Debug
    debug::add(3, tr("Start compiling pacakge for publication to repository, {1}", $pkg_alias));

    // Load package
    $client = app::make(package_config::class, ['pkg_alias' => $pkg_alias]);
    $pkg = $client->load();

    // Create tmp directory
    $tmp_dir = sys_get_temp_dir() . '/apex_' . $pkg_alias;
    io::create_blank_dir($tmp_dir);
    io::create_dir("$tmp_dir/etc");

    // Debug
    debug::add(4, tr("Compiling, loaded package configuration and created tmp directory for package, {1}", $pkg_alias));

    // Copy package files
    $etc_dir = SITE_PATH . '/etc/' . $pkg_alias;
    foreach (PACKAGE_CONFIG_FILES as $file) { 
        if (!file_exists("$etc_dir/$file")) { continue; }
        copy("$etc_dir/$file", "$tmp_dir/etc/$file");
    }

    // Go through components
    $rows = db::query("SELECT * FROM internal_components WHERE owner = %s ORDER BY id", $pkg_alias);
    foreach ($rows as $row) { 

        // Go through files
        $files = components::get_all_files($row['type'], $row['alias'], $row['package'], $row['parent']);
        foreach ($files as $file) { 
            $this->add_file($file, $pkg_alias);
        }
    }

    // Debug
    debug::add(4, tr("Compiling package, successfully compiled aall components and created componentss.sjon file for package, {1}", $pkg_alias));

    // External files
    foreach ($pkg->ext_files as $file) { 

        // Single file
        if (!preg_match("/^(.*?)\/\*$/", $file, $match)) { 
            $this->add_file($file, $pkg_alias, 'ext/' . $file);
            continue;
        }

        // Get all files from directory
        $files = io::parse_dir(SITE_PATH . '/' . $match[1]);
        foreach ($files as $tmp_file) { 
            $this->add_file($match[1] . '/' . $tmp_file, $pkg_alias, 'ext/' . $match[1] . '/' . $tmp_file);
        }
    }

    // docs and /src/tpl/ dirclearectories
    $addl_dirs = array(
        'docs/' . $pkg_alias => 'docs', 
        'src/' . $pkg_alias . '/tpl' => 'src/tpl'
    );
    foreach ($addl_dirs as $dir => $dest_dir) { 
        if (!is_dir(SITE_PATH . '/' . $dir)) { continue; }
        $addl_files = io::parse_dir(SITE_PATH . '/' . $dir);
        foreach ($addl_files as $file) {
            $this->add_file($dir . '/' . $file, $pkg_alias, $dest_dir . '/' . $file);
        }
    }

    // Send RPC and export any needed data
    $msg = new event_message('core.packages.compile', $pkg_alias);
    $response = msg::dispatch($msg)->get_response();
    foreach ($response as $export_package => $data) { 
        if (!is_array($data)) { continue; }
        foreach ($data as $filename => $filepath) { 
            if (!file_exists($filepath)) { continue; }
            $this->add_file($filepath, $pkg_alias, 'data/' . $export_package . '/' . $filename);
            @unlink($filepath);
        }
    }

    // Debug
    debug::add(3, tr("Successfully compiled package for publication, {1}", $pkg_alias));

    // Return
    return $tmp_dir;

}

/**
 * Compiles a package, and uploads it to the appropriate repository. 
 *
 * @param string $pkg_alias The alias of the package to publish.
 * @param string $version The version of the package being published (eg. 1.0.4)
 *
 * @return bool Whther or not the operation was successful.
 */
public function publish(string $pkg_alias, string $version = ''):bool
{ 

    // Get package
    if (!$row = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", $pkg_alias)) { 
        throw new PackageException('not_exists', $pkg_alias);
    }
    if ($version == '') { $version = $row['version']; }

    // Compile
    $tmp_dir = $this->compile($pkg_alias);

    // Create zip file
    $zip_file = 'apex_package_' . $pkg_alias . '-' . str_replace(".", "_", $version) . '.zip';
    io::create_zip_archive($tmp_dir, sys_get_temp_dir() . '/' . $zip_file);

    // Load package
    $client = new package_config($pkg_alias);
    $pkg = $client->load();

    // Check for readme file
    $readme_file = SITE_PATH . '/docs/' . $pkg_alias . '/index.md';
    $readme = file_exists($readme_file) ? base64_encode(file_get_contents($readme_file)) : '';

    // Get git repo url
    if (isset($pkg->git_upstream_url) && $pkg->git_upstream_url != '') { 
        $git_url = $pkg->git_upstream_url;
    } else { 
        $git_url = $pkg->git_repo_url ?? '';
    }

    // Set request
    $request = array(
        'access' => $pkg->access,
        'price' => $pkg->price ?? 0, 
        'git_url' => $git_url, 
        'name' => $pkg->name,
        'description' => $pkg->description,
        'readme' => $readme, 
        'version' => ($version == '' ? $row['version'] : $version)
    );

    // Send HTTP request
    $network = app::make(network::class);
    $vars = $network->send_repo_request((int) $row['repo_id'], $pkg_alias, 'publish', $request, false, $zip_file);

    // Debug
    debug::add(1, tr("Successfully published the package to repository, {1}", $pkg_alias));

    // Return
    return true;

}

/**
 * Fully install a package.  Downoads the package from the appropriate 
 * repository, unpacks it, and installed it. 
 *
 * @param string $pkg_alias The alias of the packagte to install
 * @param int $repo_id Optional ID# of repo to download from.  If not specified, all repos are searched.
 */
public function install(string $pkg_alias, int $repo_id = 0)
{ 

    // Debug
    debug::add(3, tr("Starting download and install of package, {1}", $pkg_alias));

    // Download package
    list($tmp_dir, $repo_id, $vars) = $this->download($pkg_alias, $repo_id);

    // Add to database
    $package_id = $this->insert($repo_id, $pkg_alias, $vars['name'], 'public', $vars['version']);

    // Install
    $this->install_from_dir($pkg_alias, $tmp_dir);

    // Clean up
    io::remove_dir($tmp_dir);
    db::clear_cache();


}

/**
 * Download a package from a repository, and unpack it into the tmp system 
 * directory 
 *
 * @param string $pkg_alias The alias of the package to download
 * @param int $repo_id Optional ID# of repo to download from.  If not specified, all repos are searched.
 *
 * @return string Directory path of where the package was unpacked at
 */
public function download(string $pkg_alias, int $repo_id = 0)
{ 

    // Debug
    debug::add(3, tr("Starting download of package, {1}", $pkg_alias));

    // Initialize
    $network = app::make(network::class);

    // Get repo, if needed
    if ($repo_id == 0) { 

        // Check package on all repos
        $repos = $network->check_package($pkg_alias);
        if (count($repos) == 0) { 
            throw new ApexException('error', "The package does not exist in any repositories listed within the system, {1}", $pkg_alias);
        }
        $repo_id = array_keys($repos)[0];
    }

    // Get repo
    $repo = db::get_idrow('internal_repos', $repo_id);

    // Send request
    $vars = $network->send_repo_request((int) $repo_id, $pkg_alias, 'download');

    // Download zip file
    $zip_file = sys_get_temp_dir() . '/apex_' . $pkg_alias . '.zip';
    if (file_exists($zip_file)) { @unlink($zip_file); }
    io::download_file($vars['download_url'], $zip_file);

    // Unpack zip file
    $tmp_dir = sys_get_temp_dir() . '/apex_' . $pkg_alias;
    io::unpack_zip_archive($zip_file, $tmp_dir);
    @unlink($zip_file);

    // Rename directory, if from git repo
    if (is_dir($tmp_dir . '/' . $pkg_alias . '-master')) { 
        $tmp_dir .= '/' . $pkg_alias . '-master';
    }

    // Debug
    debug::add(3, tr("Successfully downloaded package {1} and unpacked it at {2}", $pkg_alias, $tmp_dir));

    // Return
    return array($tmp_dir, $repo_id, $vars);

}

/**
 * Install package from directory 
 *
 * Install a package from a directory.  This assumes the package has already 
 * been downloaded, unpacked on the server, and added to the database 
 *
 * @param string $pkg_alias The alias of the package being installed
 * @param string $tmp_dir The directory where the package is currently unpacked
 */
public function install_from_dir(string $pkg_alias, string $tmp_dir)
{ 

    // Create /pkg/ directory
    $pkg_dir = SITE_PATH . '/etc/' . $pkg_alias;
    io::create_dir($pkg_dir);

    // Debug
    debug::add(4, tr("Starting package install from unpacked directory of package, {1}", $pkg_alias));

    // Copy over files
    pkg_component::sync_from_dir($pkg_alias, $tmp_dir);

    // Debug
    debug::add(4, tr("Installing package, copied over all files to correct location, package {1}", $pkg_alias));

    // Run install SQL, if needed
    io::execute_sqlfile("$pkg_dir/install.sql");
    debug::add(4, tr("Installing package, ran install.sql file for package {1}", $pkg_alias));

    // Load package
    $client = new Package_config($pkg_alias);
    $pkg = $client->load();

    // Execute PHP, if needed
    if (method_exists($pkg, 'install_before')) { 
        $pkg->install_before();
    }
    debug::add(4, tr("Installing package, loaded configuration and executed any needed PHP for package, {1}", $pkg_alias));

    // Install dependeencies
    foreach ($pkg->dependencies as $alias) { 
        if (check_package($alias) === true) { continue; }
        $this->install($alias);
    }

    // Install configuration
    $client->install_configuration();
    $client->install_notifications($pkg);
    $client->install_default_dashboard_items($pkg);
    $client->scan_workers();

    // Debug
    debug::add(4, tr("Installing package, successfully installed configuration for package, {1}", $pkg_alias));

    // Run install_after SQL, if needed
    io::execute_sqlfile("$pkg_dir/install_after.sql");
    debug::add(4, tr("Installing package, successfully installed all components for package, {1}", $pkg_alias));

    // Execute PHP, if needed
    if (method_exists($pkg, 'install_after')) { 
        $pkg->install_after();
    }

    // Copy over addl .tpl files, if needed
    $tpl_dir = SITE_PATH . '/src/' . $pkg_alias . '/tpl';
    if (is_dir($tpl_dir)) { 
        $tpl_files = io::parse_dir($tpl_dir);
        foreach ($tpl_files as $file) { 
            $dest_file = SITE_PATH . '/views/tpl/' . $file;

            // Copy over backup, if needed
            if (file_exists($dest_file)) { 
                $bak_file = SITE_PATH . '/views/tpl_bak/' . $file;
                io::create_dir(dirname($bak_file));
                copy($dest_file, $bak_file);
                @unlink($dest_file);
            }
            copy("$tpl_dir/$file", $dest_file);
        }
    }

    // Debug
    debug::add(1, tr("Successfully installed package from directory, {1}", $pkg_alias));

    // Return
    return true;

}

/**
 * Remove a package 
 *
 * @param string $pkg_alias The alias of the package to remove
 */
public function remove(string $pkg_alias)
{ 

    // Debug
    debug::add(1, tr("Starting removal of package, {1}", $pkg_alias));

    // Get package from DB
    if (!$pkg_row = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", $pkg_alias)) { 
        throw new PackageException('not_exists', $pkg_alias);
    } elseif ($pkg_alias == 'core') { 
        throw new ApexException('error', "You can not remove the core package!");
    }

    // Load package
    $pkg_client = new package_config($pkg_alias);
    $pkg = $pkg_client->load();
    $pkg_dir = SITE_PATH . '/etc/' . $pkg_alias;

    // Run remove_after SQL, if needed
    io::execute_sqlfile("$pkg_dir/remove.sql");

    // Debug
    debug::add(4, tr("Removing package, successfully loaded configuration and executed remove.sql SQL for package, {1}", $pkg_alias));

    // Delete all components
    $comp_rows = db::query("SELECT * FROM internal_components WHERE owner = %s OR package = %s ORDER BY id DESC", $pkg_alias, $pkg_alias);
    foreach ($comp_rows as $crow) { 

        // Get comp alias
        if ($crow['type'] == 'view') { 
            $comp_alias = $crow['alias'];
        } else { 
            $comp_alias = $crow['parent'] == '' ? $crow['package'] . ':' . $crow['alias'] : $crow['package'] . ':' . $crow['parent'] . ':' . $crow['alias'];
        }

        // Delete component
        pkg_component::remove($crow['type'], $comp_alias);
    }

    // Delete all ext files
    foreach ($pkg->ext_files as $file) { 
        if (preg_match("/^(.+)\*$/", $file, $match)) { 
            io::remove_dir(SITE_PATH . '/' . $match[1]);
        } elseif (file_exists(SITE_PATH . '/' . $file)) { 
            @unlink(SITE_PATH . '/' . $file);
        }
    }
    if (is_dir(SITE_PATH . '/docs/' . $pkg_alias)) { io::remove_dir(SITE_PATH . '/docs/' . $pkg_alias); }

    // Debug
    debug::add(4, tr("Removing package, successfully deleted all components from package, {1}", $pkg_alias));

    // Remove package directories
    io::remove_dir($pkg_dir);
    io::remove_dir(SITE_PATH . '/src/' . $pkg_alias);
    io::remove_dir(SITE_PATH . '/tests/' . $pkg_alias);

    // Remove from database
    db::query("DELETE FROM internal_packages WHERE alias = %s", $pkg_alias);
    db::query("DELETE FROM cms_menus WHERE package = %s", $pkg_alias);

    // Delete composer dependencies, if needed
    if (count($pkg->composer_dependencies) > 0) { 
        $composer = json_decode(file_get_contents(SITE_PATH . '/composer.json'), true);
        foreach ($pkg->composer_dependencies as $key => $version) { 
            if (!isset($composer['require'][$key])) { continue; }
            unset($composer['require'][$key]);
        }
        file_put_contents(SITE_PATH . '/composer.json', json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    // Execute PHP, if needed
    if (method_exists($pkg, 'remove')) { 
        $pkg->remove();
    }

    // Debug
    debug::add(1, tr("Successfully removed the package, {1}", $pkg_alias));

    // Return
    return true;

}

/**
 * Adds a file to an archive,, and is used while compiling a package. 
 *
 * @param string $filename The filename to add, relative to the / installation directory.
 * @param string $pkg_alias The alias of the package we're adding to.
 * @param string $tmp_file Optional filename to force file into.
 */
private function add_file(string $filename, string $pkg_alias, string $tmp_file = '')
{ 

    // Initialize
    $tmp_dir = sys_get_temp_dir() . '/apex_' . $pkg_alias;
    if (!file_exists($filename)) { $filename = SITE_PATH . '/' . $filename; }
    if (!file_exists($filename)) { return; }

    // Get tmp file
    if ($tmp_file == '') { 
        $tmp_file = components::get_compile_file($filename, $pkg_alias);
    }

    // Check tabpage ownership, if needed
    if (preg_match("/^src\/tabcontrol\/(.+?)\/(.+?)\.php$/", $tmp_file, $match)) { 
        if ($chk_owner = db::get_field("SELECT owner FROM internal_components WHERE type = 'tabpage' AND package = %s AND parent = %s AND alias = %s", $pkg_alias, $match[1], $match[2])) { 
            if ($chk_owner != $pkg_alias) { return; }
        }
    }

    // Copy file
    io::create_dir(dirname("$tmp_dir/$tmp_file"));
    copy($filename, "$tmp_dir/$tmp_file");

    // Debug
    debug::add(5, tr("Added file to TOC during package compile, {1}", $filename));

}

/**
 * Compile Apex core framework 
 *
 * Compiles the core Apex framework into a temporary directory.  Gnerally only 
 * used by Apex to generate the necessary directory / file structure for the 
 * Github repo. 
 */
public function compile_core()
{ 

    // Load core package
    require_once(SITE_PATH . '/etc/core/package.php');
    $pkg = new \apex\pkg_core();

    // Create destination dir
    $destdir = sys_get_temp_dir() . '/apex';
    if (is_dir($destdir)) { io::remove_dir($destdir); }
    io::create_dir($destdir);

    // Go through external files
    foreach ($pkg->ext_files as $file) { 

        // Copy directory
        if (preg_match("/^(.*?)\/\*$/", $file, $match)) { 
            io::create_dir(dirname("$destdir/" . $match[1] . '.php'));
            system("cp -R " . SITE_PATH . "/$match[1]/ $destdir/$match[1]/");
            continue;
        }

        // Check if file exists
        if (!file_exists(SITE_PATH . "/$file")) { 
            throw new ApexException('error', "The external file does not exist, {1}", $file);
        }

        // Copy file as needed
        io::create_dir(dirname("$destdir/$file"));
        copy(SITE_PATH . "/$file", "$destdir/$file");
    }

    // Create /storage/ directory
    io::create_dir("$destdir/storage/logs");
    file_put_contents("$destdir/storage/logs/access.log", '');

    // Go through components
    $rows = db::query("SELECT * FROm internal_components WHERE owner = 'core' ORDER BY id");
    foreach ($rows as $row) { 

        // Get files
        $files = components::get_all_files($row['type'], $row['alias'], $row['package'], $row['parent']);
        $has_php = false;
        foreach ($files as $file) { 
            if (preg_match("/\.php$/", $file)) { $has_php = true; }

            if (!file_exists(SITE_PATH . '/' . $file)) { continue; }
            io::create_dir(dirname("$destdir/$file"));
            copy(SITE_PATH . '/' . $file, "$destdir/$file");
        }
    }

    // Copy over package files
    foreach (PACKAGE_CONFIG_FILES as $file) { 
        if (!file_exists(SITE_PATH . '/etc/core/' . $file)) { continue; }

        // Copy, if not package.php
        if ($file != 'package.php') { 
            copy(SITE_PATH . '/etc/core/' . $file, $destdir . '/etc/core/' . $file);
            continue;
        }

        // Update version in package.php file
        $version = db::get_field("SELECT version FROM internal_packages WHERE alias = 'core'");
        $text = preg_replace("/\\\$version \= (.*?)\;/", "\$version = '$version';", file_get_contents(SITE_PATH . '/etc/core/package.php'));
        file_put_contents($destdir . '/etc/core/package.php', $text);
    }

    // Save blank .env file
    file_put_contents("$destdir/.env", base64_decode('CiMgCiMgVGhpcyBpcyBhIGJsYW5rIC5lbnYgZmlsZSwgbWVhbmluZyBpbnN0YWxsYXRpb24gaGFzIG5vdCB5ZXQgYmVlIGNvbXBsZXRlZC4KIyAKIyBQbGVhc2UgY29tcGxldGUgaW5zdGFsbGF0aW9uIGZpcnN0LiAgV2l0aGluIHRlcm1pbmFsLCB0eXBlOgojCiMgICAgIC4vYXBleAojCiMgT25jZSBpbnN0YWxsYXRpb24gaGFzIGJlZW4gY29tcGxldGVkLCB0aGlzIGZpbGUgd2lsbCBiZSBwb3B1bGF0ZWQgd2l0aCB2YXJpYWJsZXMgdGhhdCB5b3UgCiMgbWF5IGVkaXQgYXMgbmVjZXNzYXJ5LgojCgo='));

    // Create /docs directory
    $files = io::parse_dir(SITE_PATH . '/docs', false);
    foreach ($files as $file) { 
        if (preg_match("/\//", $file)) { continue; }
        if (!preg_match("/\.md$/", $file)) { continue; }
        copy(SITE_PATH . "/docs/$file", "$destdir/docs/$file");
    }
    chmod("$destdir/apex", 0755);

    // Update GIthub repo
    $this->update_github_repo();

    // Return
    return $destdir;

}

/**
 * Update the Github repo hosted locally 
 */
private function update_github_repo()
{ 

    // Initialize
    $rootdir = sys_get_temp_dir() . '/apex';
    if (!$git_dir = realpath('../apex_git')) { return; }
    if (!is_dir($git_dir)) { return; }
    $git_cmds = array('#!/bin/sh');

    // Create Git file hash
    $git_hash = array();
    $files = io::parse_dir($git_dir);
    foreach ($files as $file) { 
        if (preg_match("/^\.git/", $file)) { continue; }
        $git_hash[$file] = sha1(file_get_contents("$git_dir/$file"));
    }

    // Go through all files
    $files = io::parse_dir($rootdir);
    foreach ($files as $file) { 

        // Get file hashes
    $hash = sha1(file_get_contents("$rootdir/$file"));
        $chk_hash = $git_hash[$file] ?? '';

        // Check file hash
    if ($chk_hash == '' || $hash != $chk_hash) { 
        if (file_exists("$git_dir/$file")) { @unlink("$git_dir/$file"); }
        if (!is_dir(dirname("$git_dir/$file"))) { 
            mkdir(dirname("$git_dir/$file"));
        }
        copy("$rootdir/$file", "$git_dir/$file");
        $git_cmds[] = "git add $file";
    }
        if (isset($git_hash[$file])) { unset($git_hash[$file]); }
    }

    // Delete files
    foreach ($git_hash as $file => $hash) { 
        if (preg_match("/^\./", $file)) { continue; }
        @unlink("$git_dir/$file");
        $git_cmds[] = "git rm $file";
    }

    // Save git.sh file
    file_put_contents("$git_dir/git.sh", implode("\n", $git_cmds));
    chmod ("$git_dir/git.sh", 0755);

}

/**
 * Reset a package.  Executes any SQL file at /etc/PKG_ALIAS/reset.sql, and 
 * executes any reset() function within the package.php file. 
 *
 * @param string $pkg_alias The alias of the package to reset.
 */
public function reset(string $pkg_alias)
{ 

    // Load package
    $client = new package_config($pkg_alias);
    $pkg = $client->load();

    // Execute SQL, if available
    io::execute_sqlfile(SITE_PATH . '/etc/' . $pkg_alias . '/reset.sql');

    // Execute reset method, if available
    if (method_exists($pkg, 'reset')) { 
        $pkg->reset();
    }

    // Execute reset_redis method, if available
    if (method_exists($pkg, 'reset_redis')) { 
        $pkg->reset_redis();
    }

    // Return
    return true;

}

/**
 * Reinstall components.  Deletes all existing components of a packagew from 
 * the database, and re-installs them using the /etc/PACKAGE/components.json 
 * file. 
 *
 * @param string $pkg_alias The alias of the package to re-install components for.
 */
public function reinstall_components(string $pkg_alias)
{ 

    // Remove existing component rows
    db::query("DELETE FROM internal_components WHERE owner = %s", $pkg_alias);

    // Go through components
    $components = json_decode(file_get_contents(SITE_PATH . '/etc/' . $pkg_alias . '/components.json'), true);
    foreach ($components as $row) { 
        if ($row['type'] == 'template') { $comp_alias = $row['alias']; }
        else { $comp_alias = $row['parent'] == '' ? $row['package'] . ':' . $row['alias'] : $row['package'] . ':' . $row['parent'] . ':' . $row['alias']; }

        pkg_component::add($row['type'], $comp_alias, $row['value'], (int) $row['order_num'], $pkg_alias);
    }

}


}

