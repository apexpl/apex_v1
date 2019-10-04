<?php
declare(strict_types = 1);

namespace apex\app\pkg;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\app\sys\network;
use apex\svc\io;
use apex\svc\components;
use apex\app\pkg\package_config;
use apex\app\exceptions\ApexException;
use apex\app\exceptions\PackageException;
use apex\app\exceptions\RepoException;
use CurlFile;


/**
 * Handles all package functions -- create, compile, download, install, 
 * remove. 
 */
class package
{



    // Properties
    private $tmp_dir;
    private $toc = array();
    private $file_num = 1;
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
    $pkg_file = base64_decode('PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4OwoKdXNlIGFwZXhcYXBwOwp1c2UgYXBleFxzdmNcZGI7CnVzZSBhcGV4XHN2Y1xyZWRpczsKCgovKioKICogVGhlIHBhY2thZ2UgY29uZmlndXJhdGlvbiwgaW5jbHVkaW5nIHZhcmlvdXMgcHJvcGVydGllcywgCiAqIGFuZCBQSFAgY29kZSB0byBiZSBleGVjdXRlZCBkdXJpbmcgaW5zdGFsbCwgcmVzZXQsIGFuZCAKICogcmVtb3ZhbCBvZiB0aGUgcGFja2FnZS4KICovCmNsYXNzIHBrZ19+YWxpYXN+IAp7CgogICAgLy8gQmFzaWMgcGFja2FnZSB2YXJpYWJsZXMKICAgIHB1YmxpYyAkYWNjZXNzID0gJ35hY2Nlc3N+JzsKICAgIHB1YmxpYyAkbmFtZSA9ICd+bmFtZX4nOwogICAgcHVibGljICRkZXNjcmlwdGlvbiA9ICcnOwoKLyoqCiAqIERlZmluZSBwYWNrYWdlIGNvbmZpZ3VyYXRpb24uCiAqCiAqIFRoZSBjb25zdHJ1Y3RvciB0aGF0IGRlZmluZXMgdGhlIHZhcmlvdXMgY29uZmlndXJhdGlvbiAKICogYXJyYXlzIG9mIHRoZSBwYWNrYWdlIHN1Y2ggYXMgY29uZmlnIHZhcnMsIGhhc2hlcywgbWVudXMsIGV4dGVybmFsIGZpbGVzLCBhbmQgc28gb24uCiAqCiAqIEZvciBmdWxsIGRldGFpbHMgb24gdGhpcyBmaWxlLCBwbGVhc2UgdmlzaXQgdGhlIGRvY3VtZW50YXRpb24gYXQ6CiAqICAgICBodHRwczovL2FwZXgtcGxhdGZvcm0ub3JnL2RvY3MvcGFja2FnZV9jb25maWcKICovCnB1YmxpYyBmdW5jdGlvbiBfX2NvbnN0cnVjdCgpIAp7CgogICAgLyoqCiAgICAgKiBDb25maWcgVmFyaWFibGVzCiAgICAgKiAKICAgICAqICAgICBBcnJheSBvZiBrZXktdmFsdWUgcGFpcnMgZm9yIGFkbWluIC9zeXN0ZW0gCiAgICAgKiAgICAgZGVmaW5lZCBzZXR0aW5ncywgYXZhaWxhYmxlIHZpYSB0aGUgYXBwOjpfY29uZmlnKCRrZXkpIG1ldGhvZC4KICAgICAqLwogICAgJHRoaXMtPmNvbmZpZyA9IGFycmF5KCk7CgoKICAgIC8qKgogICAgICogSGFzaGVzCiAgICAgKiAKICAgICAqICAgICBBcnJheSBvZiBhc3NvY2lhdGl2ZSBhcnJheXMgdGhhdCBkZWZpbmUgdmFyaW91cyBsaXN0cyBvZiBrZXktdmFsdWUgcGFpcnMgdXNlZC4KICAgICAqICAgICBVc2VkIGZvciBxdWlja2x5IGdlbmVyYXRpbmcgc2VsZWN0IC8gY2hlY2tib3ggLyByYWRpbyBsaXN0cyB2aWEgCiAgICAgKiBoYXNoZXM6OmNyZWF0ZV9vcHRpb25zIC8gaGFzaGVzOmdldF9oYXNoX3ZhcigpIG1ldGhvZHMuCiAgICAgKi8KICAgICR0aGlzLT5oYXNoID0gYXJyYXkoKTsKCgogICAgLyoqCiAgICAgKiBNZW51cwogICAgICoKICAgICAqICAgICBNZW51cyBmb3IgdGhlIGFkbWluaXN0cmF0aW9uIHBhbmVsLCBtZW1iZXIncyBhcmVhLCAKICAgICAqICAgICAgYW5kIHB1YmxpYyBzaXRlLiAgUGxlYXNlIHJlZmVyIHRvIGRldmVsb3BlciBkb2N1bWVudGF0aW9uIGZvciBmdWxsIGRldGFpbHMuCiAgICAgKi8KICAgICR0aGlzLT5tZW51cyA9IGFycmF5KCk7CgoKICAgIC8qKgogICAgICogRXh0ZXJuYWwgRmlsZXMKICAgICAqCiAgICAgKiAgICAgT25lIGRpbWVuc2lvbmFsIGFycmF5IG9mIGFsbCBleHRlcm5hbCBmaWxlcywgcmVsYXRpdmUgdG8gdGhlIGluc3RhbGxhdGlvbiBkaXJlY3RvcnkuCiAgICAgKiAgICAgKGVnLiAnL3BsdWdpbnMvc29tZWRpci9teV9wbHVnaW4uanMnKQogICAgICovCiAgICAkdGhpcy0+ZXh0X2ZpbGVzID0gYXJyYXkoKTsKCgogICAgLyoqCiAgICAgKiBQbGFjZWhvbGRlcnMKICAgICAqCiAgICAgKiBBc3NvY2lhdGl2ZSBhcnJheXMsIGtleXMgYmVpbmcgYSBVUkksIGFuZCB2YWx1ZXMgYmVpbmcgYW4gYXJyYXkgb2YgcGxhY2Vob2xkZXIgCiAgICAgKiBhbGlhc2VzLiAgVmFsdWVzIG9mIGVhY2ggcGxhY2Vob2xkZXIgY2FuIGJlIGRlZmluZWQgd2l0aGluIHRoZSAKICAgICAqIGFkbWluaXN0cmF0aW9uIHBhbmVsLCBhbmQgcGxhY2VkIHdpdGhpbiBwYWdlcyBpbiB0aGUgcHVibGljIAogICAgICogc2l0ZSAvIG1lbWJlcidzIGFyZWEgd2l0aCBhIHRhZyBzdWNoIGFzOgogICAgICoKICAgICAqICAgICA8YTpwbGFjZWhvbGRlciBhbGlhcysiQUxJQVMiPgogICAgICoKICAgICAqIFRoaXMgYWxsb3dzIHlvdSBhcyB0aGUgZGV2ZWxvcGVyIHRvIGluY2x1ZGUgdmlld3Mgd2l0aGluIHVwZ3JhZGVzLCBhbmQgCiAgICAgKiB0aGUgYWRtaW5pc3RyYXRvciB0byByZXRhaW4gdGhlIHRleHR1YWwgbW9kaWZpY2F0aW9ucyB3aXRob3V0IHRoZW0gYmVpbmcgb3ZlcndyaXR0ZW4gCiAgICAgKiBkdWUgdG8gYW4gdXBncmFkZS4KICAgICAqLwogICAgJHRoaXMtPnBsYWNlaG9sZGVycyA9IGFycmF5KCk7CgoKICAgIC8qKgogICAgICogQm94bGlzdHMKICAgICAqIAogICAgICogVXNlZCBtYWlubHkgdG8gb3JnYW5pemUgc2V0dGluZ3MgcGFnZXMsIGFuZCBleGFtcGxlIGlzIAogICAgICogdGhlIFNldHRpbmdzLT5Vc2VycyBtZW51IG9mIHRoZSBhZG1pbmlzdHJhdGlvbiBwYW5lbC4gIFRoaXMgYXJyYXkgCiAgICAgKiBhbGxvd3MgeW91IHRvIGFkZCBpdGVtcyB0byBhbiBleGlzdGluZyBib3hsaXN0LCBvciAKICAgICAqIGNyZWF0ZSB5b3VyIG93bi4gIFBsZWFzZSByZWZlciB0byBkZXZlbG9wZXIgCiAgICAgKiBkb2N1bWVudGF0aW9uIGZvciBmdWxsIGRldGFpbHMuCiAgICAgKi8KICAgICR0aGlzLT5ib3hsaXN0cyA9IGFycmF5KCk7CgoKICAgIC8qKgogICAgICogRGVmYXVsdCBub3RpZmljYXRpb25zLgogICAgICoKICAgICAqIEFueSBkZWZhdWx0IGUtbWFpbCBub3RpZmljYXRpb25zIHlvdSB3b3VsZCBsaWtlIGF1dG9tYXRpY2FsbHkgY3JlYXRlZCAKICAgICAqIGR1cmluZyBpbnN0YWxsYXRpb24gb2YgdGhlIHBhY2thZ2UuICBwbGVhc2UgcmVmZXIgCiAgICAgKiB0byB0aGUgZGV2ZWxvcGVyIGRvY3VtZW50YXRpb24gZm9yIGZ1bGwgZGV0YWlscyBvZiB0aGlzIGFycmF5LgogICAgICovCiAgICAkdGhpcy0+bm90aWZpY2F0aW9ucyA9IGFycmF5KCk7CgoKfQoKLyoqCiAqIEluc3RhbGwgQmVmb3JlCiAqCiAqICAgICAgRXhlY3V0ZWQgYmVmb3JldGhlIGluc3RhbGxhdGlvbiBvZiBhIHBhY2thZ2UgYmVnaW5zLgogKi8KcHVibGljIGZ1bmN0aW9uIGluc3RhbGxfYmVmb3JlKCkKewoKfQoKLyoqCiAqIEluc3RhbGwgQWZ0ZXIKICoKICogICAgIEV4ZWN1dGVkIGFmdGVyIGluc3RhbGxhdGlvbiBvZiBhIHBhY2thZ2UsIG9uY2UgYWxsIFNRTCBpcyBleGVjdXRlZCBhbmQgY29tcG9uZW50cyBhcmUgaW5zdGFsbGVkLgogKi8KcHVibGljIGZ1bmN0aW9uIGluc3RhbGxfYWZ0ZXIoKSAKewoKfQoKLyoqCiAqIFJlc2V0CiAqCiAqICAgICAgRXhlY3V0ZWQgd2hlbiBhZG1pbiByZXNldHMgdGhlIHBhY2thZ2UgdG8gZGVmYXVsdCBzdGF0ZSBhZnRlciBpdCB3YXMgaW5zdGFsbGVkLgogKi8KcHVibGljIGZ1bmN0aW9uIHJlc2V0KCkgCnsKCn0KCi8qKgogKiBSZXNldCByZWRpcy4gIAogKiAKICogSXMgZXhlY3V0ZWQgd2hlbiBhZG1pbmlzdHJhdG9yIHJlc2V0cyB0aGUgcmVkaXMgZGF0YWJhc2UsIAogKiBhbmQgc2hvdWxkIHJlZ2VuZXJhdGUgYWxsIHJlZGlzIGtleXMgYXMgbmVjZXNzYXJ5IGZyb20gdGhlIG15U1FMIGRhdGFiYXNlCiAqLwpwdWJsaWMgZnVuY3Rpb24gcmVzZXRfcmVkaXMoKQp7Cgp9CgovKioKICogUmVtb3ZlCiAqCiAqICAgICAgRXhlY3V0ZWQgd2hlbiB0aGUgcGFja2FnZSBpcyByZW1vdmVkIGZyb20gdGhlIHNlcnZlci4KICovCnB1YmxpYyBmdW5jdGlvbiByZW1vdmUoKSAKewoKfQoKCn0KCg==');
    $pkg_file = str_replace("~alias~", $pkg_alias, $pkg_file);
    $pkg_file = str_replace("~version~", $version, $pkg_file);
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
 * @return string The filename or the created archive file,
 */
public function compile(string $pkg_alias):string
{ 

    // Debug
    debug::add(3, tr("Start compiling pacakge for publication to repository, {1}", $pkg_alias));

    // Load package
    $client = new package_config($pkg_alias);
    $pkg = $client->load();

    // Create tmp directory
    $tmp_dir = sys_get_temp_dir() . '/apex_' . $pkg_alias;
    io::remove_dir($tmp_dir);
    io::create_dir($tmp_dir);
    io::create_dir("$tmp_dir/files");
    $this->tmp_dir = $tmp_dir;

    // Debug
    debug::add(4, tr("Compiling, loaded package configuration and created tmp directory for package, {1}", $pkg_alias));

    // Go through components
    $components = array();
    $rows = db::query("SELECT * FROM internal_components WHERE owner = %s ORDER BY id", $pkg_alias);
    foreach ($rows as $row) { 

        // Go through files
        $has_php = false;
        $files = components::get_all_files($row['type'], $row['alias'], $row['package'], $row['parent']);
    foreach ($files as $file) { 
            if (preg_match("/\.php$/", $file)) { $has_php = true; }
            if (!file_exists(SITE_PATH . '/' . $file)) { continue; }
            $this->add_file($file);
        }
        if ($has_php === false) { continue; }

        // Add to $components array
        $vars = array(
            'type' => $row['type'],
            'order_num' => $row['order_num'],
            'package' => $row['package'],
            'parent' => $row['parent'],
            'alias' => $row['alias'],
            'value' => $row['value']
        );
        array_push($components, $vars);
    }
    file_put_contents(SITE_PATH . '/etc/' . $pkg_alias . '/components.json', json_encode($components));

    // Debug
    debug::add(4, tr("Compiling package, successfully compiled aall components and created componentss.sjon file for package, {1}", $pkg_alias));

    // Copy over basic package files
    $pkg_dir = SITE_PATH . '/etc/' . $pkg_alias;
    $files = array('components.json', 'package.php', 'install.sql', 'install_after.sql', 'reset.sql', 'remove.sql');
    foreach ($files as $file) { 
        if (!file_exists("$pkg_dir/$file")) { continue; }
        copy("$pkg_dir/$file", "$tmp_dir/$file");
    }

    // External files
    foreach ($pkg->ext_files as $file) { 

        // Check for * mark
        if (preg_match("/^(.+?)\*$/", $file, $match)) { 
            $files = io::parse_dir(SITE_PATH . '/' . $match[1]);
            foreach ($files as $tmp_file) { $this->add_file($match[1] . $tmp_file); }
        } else { 
            $this->add_file($file);
        }
    }

    // docs and /src/tpl/ dirclearectories
    $addl_dirs = array(
        'docs/' . $pkg_alias,
        'src/' . $pkg_alias . '/tpl'
    );
    foreach ($addl_dirs as $dir) { 
        if (!is_dir(SITE_PATH . '/' . $dir)) { continue; }
        $addl_files = io::parse_dir(SITE_PATH . '/' . $dir);
        foreach ($addl_files as $file) { 
            $this->add_file($dir . '/' . $file);
        }
    }

    // Save JSON file
    file_put_contents("$tmp_dir/toc.json", json_encode($this->toc));

    // Debug
    debug::add(4, tr("Compiling, gatheered all files and saved toc.json for package, {1}", $pkg_alias));

    // Create archive
    $version = db::get_field("SELECT version FROM internal_packages WHERE alias = %s", $pkg_alias);
    $archive_file = sys_get_temp_dir() . '/' . $pkg_alias . '-' . str_replace(".", "_", $version) . '.zip';
    io::create_zip_archive($tmp_dir, $archive_file);

    // Debug
    debug::add(3, tr("Successfully compiled package for publication, {1}", $pkg_alias));

    // Return
    return $archive_file;

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
    $archive_file = $this->compile($pkg_alias);

    // Load package
    $client = new package_config($pkg_alias);
    $pkg = $client->load();

    // Check for readme file
    $readme_file = SITE_PATH . '/docs/' . $pkg_alias . '/index.md';
    $readme = file_exists($readme_file) ? base64_encode(file_get_contents($readme_file)) : '';

    // Set request
    $request = array(
        'access' => $pkg->access,
        'name' => $pkg->name,
        'description' => $pkg->description,
        'readme' => $readme, 
        'version' => $version,
        'contents' => new CurlFile($archive_file, 'application/gzip', $archive_file)
    );

    // Send HTTP request
    $network = new network();
    $vars = $network->send_repo_request((int) $row['repo_id'], $pkg_alias, 'publish', $request);

    // Delete archive file
    unlink($archive_file);

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
    $network = new network();

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
    if (!$repo = db::get_idrow('internal_repos', $repo_id)) { 
        throw new RepoException('not_exists', $repo_id);
    }

    // Send request
    $vars = $network->send_repo_request((int) $repo_id, $pkg_alias, 'download');

// Save contents
    $zip_file = sys_get_temp_dir() . '/apex_' . $pkg_alias . '.zip';
    if (file_exists($zip_file)) { @unlink($zip_file); }
    file_put_contents($zip_file, base64_decode($vars['contents']));

    // Unpack zip file
    $tmp_dir = sys_get_temp_dir() . '/apex_' . $pkg_alias;
    io::unpack_zip_archive($zip_file, $tmp_dir);
    @unlink($zip_file);

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

    // Copy over /pkg/ files
    $files = array('components.json', 'package.php', 'install.sql', 'install_after.sql', 'reset.sql', 'remove.sql');
    foreach ($files as $file) { 
        if (!file_exists("$tmp_dir/$file")) { continue; }
        copy("$tmp_dir/$file", "$pkg_dir/$file");
    }

    // Copy over all files
    $toc = json_decode(file_get_contents("$tmp_dir/toc.json"), true);
    foreach ($toc as $file => $file_num) { 
        io::create_dir(dirname(SITE_PATH . '/' . $file));

        if (!file_exists("$tmp_dir/files/$file_num")) { 
            file_put_contents(SITE_PATH .'/' . $file, '');
        } else { 
            copy("$tmp_dir/files/$file_num", SITE_PATH .'/' . $file);
        }
    }

    // Debug
    debug::add(4, tr("Installing package, copied over all files to correct location, package {1}", $pkg_alias));

    // Run install SQL, if needed
    io::execute_sqlfile("$pkg_dir/install.sql");

    // Debug
    debug::add(4, tr("Installing package, ran install.sql file for package {1}", $pkg_alias));

    // Load package
    $client = new Package_config($pkg_alias);
    $pkg = $client->load();

    // Execute PHP, if needed
    if (method_exists($pkg, 'install_before')) { 
        $pkg->install_before();
    }

    // Debug
    debug::add(4, tr("Installing package, loaded configuration and executed any needed PHP for package, {1}", $pkg_alias));

    // Install configuration
    $client->install_configuration();
    $client->install_notifications($pkg);
    $client->install_default_dashboard_items($pkg);

    // Debug
    debug::add(4, tr("Installing package, successfully installed configuration for package, {1}", $pkg_alias));

    // Go through components
    $components = json_decode(file_get_contents("$pkg_dir/components.json"), true);
    foreach ($components as $row) { 
        if ($row['type'] == 'view') { $comp_alias = $row['alias']; }
        else { $comp_alias = $row['parent'] == '' ? $row['package'] . ':' . $row['alias'] : $row['package'] . ':' . $row['parent'] . ':' . $row['alias']; }

        pkg_component::add($row['type'], $comp_alias, $row['value'], (int) $row['order_num'], $pkg_alias);
    }

    // Run install_after SQL, if needed
    io::execute_sqlfile("$pkg_dir/install_after.sql");

    // Debug
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

    // Clean up
    io::remove_dir($tmp_dir);

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
    }
    if ($pkg_alias == 'core') { 
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
        $comp_alias = $crow['parent'] == '' ? $crow['package'] . ':' . $crow['alias'] : $crow['package'] . ':' . $crow['parent'] . ':' . $crow['alias'];
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

    // Update redis menus
    $pkg_client->update_redis_menus();


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
 */
private function add_file(string $filename)
{ 

    // Copy file
    copy(SITE_PATH . '/' . $filename, $this->tmp_dir . '/files/' . $this->file_num);

    // Add to TOC
    $this->toc[$filename] = $this->file_num;
    $this->file_num++;

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
    $components = array();
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

        // Add to $components array
        if ($has_php === true) { 
            $vars = array(
                'order_num' => $row['order_num'],
                'type' => $row['type'],
                'package' => $row['package'],
                'parent' => $row['parent'],
                'alias' => $row['alias'],
                'value' => $row['value']
            );
            array_push($components, $vars);
        }
    }
    file_put_contents(SITE_PATH .'/etc/core/components.json', json_encode($components));

    // Copy over package files
    $pkg_files = array('components.json', 'install.sql', 'reset.sql', 'remove.sql', 'package.php');
    foreach ($pkg_files as $file) { 
        if (!file_exists(SITE_PATH . '/etc/core/' . $file)) { continue; }

        // Copy, if not package.php
        if ($file != 'package.php') { 
            copy(SITE_PATH . '/etc/core/' . $file, $destdir . '/etc/core/' . $file);
            continue;
        }

        // Update version in package.php file
        $version = db::get_field("SELECT version FROM internal_packages WHERE alias = 'core'");
        $text = str_replace("~version~", $version, file_get_contents(SITE_PATH . '/etc/core/package.php'));
        file_put_contents($destdir . '/etc/core/package.php', $text);
    }

    // Save blank /etc/config.php file
    file_put_contents("$destdir/etc/config.php", "<?php\n\n");

    // Create /docs directory
    $files = io::parse_dir(SITE_PATH . '/docs', false);
    foreach ($files as $file) { 
        if (preg_match("/\//", $file)) { continue; }
        if (!preg_match("/\.md$/", $file)) { continue; }
        copy(SITE_PATH . "/docs/$file", "$destdir/docs/$file");
    }

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
        io::create_dir(dirname("$git_dir/$file"));
        copy("$rootdir/$file", "$git_dir/$file");
        $git_cmds[] = "git add $file";
    }
        if (isset($git_hash[$file])) { unset($git_hash[$file]); }
    }

    // Delete files
    foreach ($git_hash as $file => $hash) { 
        if (preg_match("/^\./", $file)) { continue; }
        @unlink("$git_dir/$file");
        $git_cmds[] = "rm $file";
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

