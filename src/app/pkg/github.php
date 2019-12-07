<?php
declare(strict_types = 1);

namespace apex\app\pkg;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\svc\io;
use apex\svc\components;
use apex\app\pkg\package;
use apex\app\pkg\package_config;
use apex\app\exceptions\PackageException;

/**
 * Handles all integration between Github and Apex packages, 
 * allowing for the full collaboration functionality within Github to 
 * be utilized while developing Apex packages.  For full details, 
 * please view the documentation at:
 *     https://apex-platform.org/docs/github
 */
class github
{

    // Properties
    private $default_readme = 'CiMgfm5hbWV+CgpUaGlzIGlzIHRoZSBHaXRodWIgcmVwb3NpdG9yeSBmb3IgdGhlIH5uYW1lfiBwYWNrYWdlLCBkZXZlbG9wZWQgZm9yIHRoZSBBcGV4IFNvZnR3YXJlIFBsYXRmb3JtIChodHRwczovL2FwZXgtcGxhdGZvcm0ub3JnLykuCgpQbGVhc2UgZG8gbm90IHRyeSB0byB1c2UgdGhpcyBwYWNrYWdlIHNlcGFyYXRlbHkuICBJbnN0ZWFkLCB5b3UgbXVzdCBmaXJzdCBpbnN0YWxsIEFwZXggd2l0aCB0aGUgaW5zdHJ1Y3Rpb25zIGF0OgogICAgaHR0cHM6Ly9hcGV4LXBsYXRmb3JtLm9yZy9kb2NzL2luc3RhbGwKCk9uY2UgaW5zdGFsbGVkLCB5b3UgbWF5IGluc3RhbGwgdGhpcyBwYWNrYWdlIGJ5IHJ1bm5pbmcgdGhlIGZvbGxvd2luZyBjb21tYW5kIHdpdGhpbiB0aGUgQXBleCBpbnN0YWxsYXRpb24gZGlyZWN0b3J5OgoKICAgIHBocCBhcGV4LnBocCBpbnN0YWxsIH5hbGlhc34KCgo=';

    // Set destination dirs
    private $dest_dirs = array(
        'child' => 'src', 
        'docs' => 'docs/~alias~', 
        'etc' => 'etc/~alias~',
        'ext' => '',  
        'src' => 'src/~alias~', 
        'tests' => 'tests/~alias~', 
        'views' => 'views'
    );


/**
 * Initialize a local Github repo.
 *
 * @param string $pkg_alias The package alias to initialize.
 */
public function init(string $pkg_alias)
{

    // Get package
    if (!$row = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", $pkg_alias)) { 
        throw new PackageException('not_exists', $pkg_alias);
    }

    // Check if already initialized
    if (is_dir(SITE_PATH . '/src/' . $pkg_alias . '/git/.git')) { 
        throw new PackageException('git_already_init', $pkg_alias);
    }

    // Load package config
    $client = app::make(package_config::class, ['pkg_alias' => $pkg_alias]);
    $pkg = $client->load();

    // Compile and compare github repo
    $git_cmd = $this->compare($pkg_alias);
    file_put_contents(SITE_PATH . '/src/' . $pkg_alias . '/git/commit.txt', "Initial commit\n");

    // Set Github variables
    $repo_url = $pkg->git_repo_url ?? '';
    $upstream_url = $pkg->git_upstream_url ?? '';
    $branch_name = $pkg->git_branch_name ?? 'master';

    // Check for repo URL
    if ($repo_url == '') { 
        throw new PackageException('git_undefined_repo_url', $pkg_alias);
    }

    // Create git.sh file
    $git = "#!/bin/sh\n";
    $git .= "git init\n";
    $git .= "git remote add origin $repo_url\n";
    if ($branch_name != 'master') { $git .= "git checkout -b $branch_name\n"; }
    if ($upstream_url != '') { $git .= "git remote add upstream $upstream_url\n"; }
    $git .= implode("\n", $git_cmd) . "\n";
    //$git .= "git tag $row[version]\n";
    $git .= "git commit --file=" . SITE_PATH . "/src/$pkg_alias/git/commit.txt\n";
    $git .= "git push -u origin $branch_name\n";

    // Save git.sh file
    file_put_contents(SITE_PATH . '/src/' . $pkg_alias . '/git/git.sh', $git);
    chmod(SITE_PATH . '/src/' . $pkg_alias . '/git/git.sh', 0755);

    // Debug
    debug::add(2, tr("Completed initializing local Github repository for package {1}", $pkg_alias));

    // Return
    return true;

}

/**
 * Compile a local Github repo
 * 
 * @param string $pkg_alias The alias of the package to compile'
 */
protected function compile(string $pkg_alias)
{

    // Debug 
    debug::add(2, tr("Starting to compile local Github repo for package {1}", $pkg_alias));

    // Load package config
    $client = app::make(package_config::class, ['pkg_alias' => $pkg_alias]);
    $pkg = $client->load();

    // Compile the package
    $package = app::make(package::class);
    $package->compile($pkg_alias);

    // Create tmp directory
    $tmp_dir = sys_get_temp_dir() . '/apex_git_' . $pkg_alias;
    if (is_dir($tmp_dir)) { io::remove_dir($tmp_dir); }
    io::create_dir($tmp_dir);

    // Create git directory
    $git_dir = SITE_PATH . '/src/' . $pkg_alias . '/git';
    io::create_dir($git_dir);

    // Create sub-directories
    $dirs = array('child', 'docs', 'etc', 'ext', 'src', 'tests', 'views', 'views/tpl', 'views/php');
    foreach ($dirs as $dir) { 
        io::create_dir("$tmp_dir/$dir");
        io::create_dir("$git_dir/$dir");
    }

    debug::add(4, tr("Created root directories for local Github repository for package {1}", $pkg_alias));

    // Copy package files
    $etc_dir = SITE_PATH . '/etc/' . $pkg_alias;
    foreach (PACKAGE_CONFIG_FILES as $file) { 
        if (!file_exists("$etc_dir/$file")) { continue; }
        copy("$etc_dir/$file", "$tmp_dir/etc/$file");
    }

    // Save Readme.md and License.txt files
    $readme = base64_decode($this->default_readme);
    $readme = str_replace("~alias~", $pkg_alias, $readme);
    $readme = str_replace("~name~", $pkg->name, $readme);
    file_put_contents("$tmp_dir/Readme.md", $readme); 
    copy(SITE_PATH . '/License.txt', "$tmp_dir/License.txt");
    file_put_contents("$tmp_dir/commit.txt", '');

    // Go through components
    $rows = db::query("SELECT * FROM internal_components WHERE owner = %s ORDER BY id", $pkg_alias);
    foreach ($rows as $row) { 

        // Go through all files
        $files = components::get_all_files($row['type'], $row['alias'], $row['package'], $row['parent']);
        foreach ($files as $file) { 
        if (!file_exists(SITE_PATH . '/' . $file)) { continue; }

            // Get Github file
            $git_file = components::get_github_file($file, $pkg_alias);
            if ($git_file == '') { continue; }

            // Save file
            io::create_dir(dirname("$tmp_dir/$git_file"));
            copy(SITE_PATH . '/' . $file, "$tmp_dir/$git_file");
        }
    }

    // Debug
    debug::add(4, tr("Completed copying over all source code files for local Github repository for package {1}", $pkg_alias));

    // Go through external files
    $ext_files = $pkg->ext_files ?? array();
    foreach ($ext_files as $file) { 

        // Single file
        if (!preg_match("/^(.+)\*/", $file, $match)) { 
            if (!file_exists(SITE_PATH . '/' . $file)) { continue; }
            io::create_dir(dirname("$tmp_dir/ext/$file"));
            copy(SITE_PATH . '/' . $file, "$tmp_dir/ext/$file");
            continue;
        }

        // Parse dir
        if (!is_dir(SITE_PATH . '/' . $match[1])) { continue; }
        $sfiles = io::parse_dir(SITE_PATH . '/' . $match[1]);
        foreach ($sfiles as $sfile) {
            $tfile = $match[1] . '/' . $sfile;
            if (!file_exists(SITE_PATH . '/' . $tfile)) { continue; }
            io::create_dir(dirname("$tmp_dir/ext/$tfile"));
            copy(SITE_PATH . '/' . $tfile, "$tmp_dir/ext/$tfile");
        }
    }

    // Documentation files
    $docs_dir = SITE_PATH . '/docs/' . $pkg_alias;
    if (is_dir($docs_dir)) { 
        $files = io::parse_dir($docs_dir);
        foreach ($files as $file) { 
            io::create_dir(dirname("$tmp_dir/docs/$file"));
            copy("$docs_dir/$file", "$tmp_dir/docs/$file");
        }
    }

    // Debug
    debug::add(4, tr("Finished creating tmp Github repo at {1}", $tmp_dir));

    // Go through all files
    $git_cmd = array();
    $files = io::parse_dir($tmp_dir);
    foreach ($files as $file) { 

        // Get hashes
        $chk_hash = file_exists("$git_dir/$file") ? sha1_file("$git_dir/$file") : '';
        $hash = sha1_file("$tmp_dir/$file");
        if ($hash == $chk_hash) { continue; }

        // Delete, if exists
        if (file_exists("$git_dir/$file")) { 
            @unlink("$git_dir/$file");
        }

        // Copy file
        io::create_dir(dirname("$git_dir/$file"));
        rename("$tmp_dir/$file", "$git_dir/$file");
        if ($file != 'commit.txt') { $git_cmd[] = "git add $file"; }
    }

    // Check for removed files
    $chk_files = io::parse_dir($git_dir);
    foreach ($chk_files as $file) { 
        if (in_array($file, $files)) { continue; }
        $git_cmd[] = "rm $file";
    }

    // Clean up
    io::remove_dir($tmp_dir);

    // Return
    return $git_cmd;

}

/**
 * Sync from Github repo to local filesystem.
 *
 * Requires the GIthub repo to already be downloaded to the local filesystem, 
 * and will go through all files, and update local filesystem with any 
 * modified files from the GIthub repo.
 *
 * @param string $pkg_alias The package alias being synced.
 */
public function sync(string $pkg_alias)
{

    // Get package
    if (!$row = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", $pkg_alias)) { 
        throw new PackageException('not_exists', $pkg_alias);
    }

    // Debug
    debug::add(2, tr("Starting to sync package with git repository, {1}", $pkg_alias));

    // Download the zip file
    $tmp_dir = $this->download_archive($pkg_alias);

    // Sync from tmp directory
    $this->sync_from_dir($pkg_alias, $tmp_dir);

    // Clean up
    io::remove_dir($tmp_dir);

    // Debug
    debug::add(2, tr("Successfully completed syncing git repository for package, {1}", $pkg_alias), 'info');

    // Return
    return true;

}

/**
 * Sync from temp directory
 *
 * @param string $pkg_alias The package alias to snyc.
 * @param string $tmp_dir The temp directory which holds the unpacked git archive.
 */
public function sync_from_dir(string $pkg_alias, string $tmp_dir)
{

    // Go through dest dirs
    foreach ($this->dest_dirs as $source_dir => $dest_dir) { 
        if (!is_dir("$tmp_dir/$source_dir")) { continue; }
        $dest_dir = str_replace("~alias~", $pkg_alias, $dest_dir);

        // Go through all files
        $files = io::parse_dir("$tmp_dir/$source_dir");
        foreach ($files as $file) { 

// Get hashes
            $dest_file = SITE_PATH . '/' . $dest_dir . '/' . $file; 
            $chk_hash = sha1_file("$tmp_dir/$source_dir/$file");
            $hash = file_exists($dest_file) ? sha1_file($dest_file) : '';
            if ($hash == $chk_hash) { continue; }

            // Delete existing, if needed
            if (file_exists($dest_file)) { 
                @unlink($dest_file);
            }

            // Debug
            debug::add(5, tr("Copying file from git repo to local, {1}", $file));

            // Copy file
            io::create_dir(dirname($dest_file));
            copy("$tmp_dir/$source_dir/$file", $dest_file);
        }
    }



}

/**
 * Compare git repo with local filesystem
 *
 * This assumes the local filesystem is more updated than the git repository.  It 
 * will download the git repo, compare all files against the local filesystem, and create the 
 * /src/PKG_ALIAS/git/git.sh file to add all updated files to the next git commit / push.
 *
 * Use this when you want to ensure the git repository has the exact 
 * same code base as the local filesystem.
 *
 * @param string $pkg_alias The package alias to compare.
 * @param string $upgrade_version Optional version if we're publishing an upgrade.
 */
public function compare(string $pkg_alias, string $upgrade_version = '')
{

    // Get package
    if (!$row = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", $pkg_alias)) { 
        throw new PackageException('not_exists', $pkg_alias);
    }

    // Debug
    debug::add(2, tr("Starting to compare remote git repo of package, {1}", $pkg_alias));

    // Load package
    $pkg_client = new package_config($pkg_alias);
    $pkg = $pkg_client->load();
    $branch_name = $pkg->git_branch_name ?? 'master';

    // Download file
    $tmp_dir = $this->download_archive($pkg_alias, true);

    // Compile package
    $this->compile($pkg_alias);

    // Get list of files
    $git_cmd = array();
    $git_dir = SITE_PATH . '/src/' . $pkg_alias . '/git';
    $files = io::parse_dir($git_dir);

    // Go through files
    foreach ($files as $file) { 

        // Skip, if needed
        if ($file == 'git.sh' || $file == 'commit.txt') { continue; }
        if (preg_match("/^\.git\//", $file)) { continue; }

        // Get hashes
        $hash = sha1_file("$git_dir/$file");
        $chk_hash = $tmp_dir !== false && file_exists("$tmp_dir/$file") ? sha1_file("$tmp_dir/$file") : '';
        if ($hash == $chk_hash) { continue; }

        // Add to git cmd
        $git_cmd[] = "git add $file";
    }

    // Delete needed files
    $tmp_files = io::parse_dir($tmp_dir);
    foreach ($tmp_files as $file) { 
        if (in_array($file, $files)) { continue; }
        $git_cmd[] = "git rm $file";
    }

    // Add upgrade comments
    if ($upgrade_version != '') { 
        $git_cmd[] = "git commit --file=" . SITE_PATH . "/src/$pkg_alias/git/commit.txt";
        $git_cmd[] = "git tag $upgrade_version";
        $git_cmd[] = "git push -u origin $branch_name --tags";
    }

    // Save git.sh file
    $git_text = "#!/bin/sh\n" . implode("\n", $git_cmd);
    file_put_contents("$git_dir/git.sh", $git_text);
    chmod("$git_dir/git.sh", 0755);

    // Debug
    debug::add(2, tr("Successfully compared remote git repository for package, {1}", $pkg_alias));

    // Return
    return $git_cmd;

}

/**
 * Download archive from git repository.
 *
 * @param string $pkg_alias The package alias to download repo of.
 * @param bool $allow_empty Whether or not to allow an empty / non-existent archive
 *
 * @return string The tmporary directory where archive is unpacked, or false if unsuccessful.
 */
private function download_archive(string $pkg_alias, bool $allow_empty = false)
{

    // Load package
    $client = app::make(package_config::class, ['pkg_alias' => $pkg_alias]);
    $pkg = $client->load();

    // Get repo URL
    $repo_url = $pkg->git_repo_url ?? '';
    if ($repo_url == '') { 
        throw new PackageException('git_undefined_repo_url', $pkg_alias);
    }
    $repo_url = rtrim(preg_replace("/\.git$/", "", $repo_url), '/') . '/archive/master.zip';

    // Debug
    debug::add(2, tr("Downloading archive file of git repository for package {1} from URL {2}", $pkg_alias, $repo_url));

    // Get zip file
    $zip_file = sys_get_temp_dir() . '/apex_git_' . $pkg_alias . '.zip';
    if (file_exists($zip_file)) { @unlink($zip_file); }

    // Download file
    io::download_file($repo_url, $zip_file);
    if ((!file_exists($zip_file)) || mime_content_type($zip_file) != 'application/zip') { 
        if ($allow_empty === true) { return false; }
        throw new PackageException('git_no_remote_archive', $pkg_alias);
    }

    // Unpack zip file
    $tmp_dir = sys_get_temp_dir() . '/apex_git_remote_' . $pkg_alias;
    if (is_dir($tmp_dir)) { io::remove_dir($tmp_dir); }
    io::unpack_zip_archive($zip_file, $tmp_dir);

    // Check tmp ddir
    if (!is_dir($tmp_dir)) { 
        throw new PackageException('git_no_remote_archive', $pkg_alias);
    }

    // Return
    return $tmp_dir . '/' . $pkg_alias . '-master';

}

/**
 * Compile components
 *
 * @param string $pkg_alias The alias of the package.
 * @param string $tmp_dir Optional temp directory to compile from, otherwise will use standard /src/PACKAGE/git/ directory.
 *
 * @return array The compiled components, same as toc.json format.
 */
public function compile_components(string $pkg_alias, string $tmp_dir = ''):array
{

    // Debug
    debug::add(1, tr("Compiling git components for package {1} in directory: {2}", $pkg_alias, $tmp_dir));

    // Check for blank tmp_dir
    if ($tmp_dir == '') { 
        $tmp_dir = SITE_PATH . '/src/' . $pkg_alias . '/git';
    }

    // Ensure directyory exists
    if (!is_dir($tmp_dir)) { 
        throw new ApexException('error', tr("Unable to compile git components for package, as directory does not exist at {1}", $tmp_dir));
    }

    // Go through /src/ directory
    $components = array();
    $files = is_dir("$tmp_dir/src") ? io::parse_dir("$tmp_dir/src") : array();
    foreach ($files as $file) {
        if (!preg_match("/\.php$/", $file)) { continue; }
        $file = preg_replace("/\.php$/", "", $file);
        $parent = '';
        $value = '';

        // Get type, parent and alias
        $parts = explode("/", $file);
        if (count($parts) == 1) { 
            list($type, $alias) = array('lib', $parts[0]);
        } elseif ($parts[0] == 'controller' && count($parts) == 3) { 
            list($type, $alias, $parent) = array($parts[0], $parts[2], $parts[1]);
        } elseif ($parts[0] == 'tabcontrol' && count($parts) == 3) { 
            list($type, $alias, $parent) = array('tabpage', $parts[2], $parts[1]);
        } elseif (in_array($parts[0], COMPONENT_TYPES)) { 
            list($type, $alias) = array($parts[0], $parts[1]);
        } else { 
            continue;
        }

        // Get routing key, if worker
        if ($type == 'worker') { 
            $worker = components::load('worker', $alias, $package);
            $value = $worker->routing_key ?? '';
        }

        // Set component vars
        $components[] = pkg_component::get_vars($type, $alias, $pkg_alias, $parent, $value);
    }

    // Debug
    debug::add(4, tr("Finished compiling /src/ directory for git package {1}", $pkg_alias));

    // Go through views
    $views = is_dir($tmp_dir . '/views/tpl') ? io::parse_dir($tmp_dir . '/views/tpl') : array();
    foreach ($views as $file) { 
        if (!preg_match("/\.tpl$/", $file)) { continue; }
        $file = preg_replace("/\.tpl/", "", $file);

        // Add component
        $components[] = pkg_component::get_vars('view', $file, $pkg_alias);
    }

    // Debug
    debug::add(4, tr("Finished compiling views for git package {1}", $pkg_alias));

    // GO through children files
    $children = is_dir(SITE_PATH . '/child') ? io::parse_dir(SITE_PATH . '/child') : array();
    foreach ($children as $file) { 
        if (!preg_match("/\.pgp$/", $file)) { continue; }
        $file = preg_replace("/\.php$/", "", $file);

        // Get component info        list($type, $
        list($package, $type, $parent, $alias) = explode('/', $file);
        if ($type == 'tabcontrol') { $type = 'tabpage'; }

        // Add to components
        $components[] = pkg_component::get_vars($type, $alias, $package, $parent);
    }

    // Debug
    debug::add(2, tr("Finished compiling git components for package {1}", $pkg_alias));

    // Return
    return $components;

}

}


