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

    // Compile Github repo
    $git_cmd = $this->compile($pkg_alias);
    file_put_contents(SITE_PATH . '/src/' . $pkg_alias . '/git/commit.txt', "Initial commit\n");

    // Set Github variables
    $repo_url = $pkg->github_repo_url ?? '';
    $upstream_url = $pkg->github_upstream_url ?? '';
    $branch_name = $pkg->github_branch_name ?? 'master';

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
private function compile(string $pkg_alias)
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
 * @param string $tmp_dir The directory which holds the cloned Github repo.
 */
public function sync(string $pkg_alias, string $tmp_dir)
{

    // Initialize
    $dest_dirs = array(
        'child' => 'src', 
        'docs' => 'docs/' . $pkg_alias, 
        'etc' => 'etc/' . $pkg_alias, 
        'src' => 'src/' . $pkg_alias, 
        'tests' => 'tests/' . $pkg_alias, 
        'views' => 'views'
    );

    // Go through dest dirs
    foreach ($dest_dirs as $source_dir => $dest_dir) { 
        if (!is_dir("$tmp_dir/$source_dir")) { continue; }

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

            // Copy file
            io::create_dir(dirname($dest_file));
            rename("$tmp_dir/$source_dir/$file", $dest_file);
        }
    }




}



