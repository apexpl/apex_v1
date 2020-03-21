<?php
declare(strict_types = 1);

namespace apex\core\cron;

use apex\app;
use apex\libc\{db, redis, io, debug};
use apex\app\pkg\remote_access;

/**
 * Handles the remote access server in cases where server permissions 
 * do not allow files to be updated via the HTTP server.
 */
class remote_update
{

    // Properties
    public $autorun = 1;
    public $time_interval = 'I2';
    public $name = 'Remote Access Updates';

/**
 * Check and process all pending remote access updates.
 */
public function process()
{


    // Copy files
    $this->copy_files();

    // Delete files
    $this->delete_files();

    // Save components
    $this->save_components();

    // Delete components
    $this->delete_components();

    // Scan packages
    $this->scan_packages();

}

/**
 * Copy files
 */
private function copy_files()
{

    // Save files
    $files = redis::hgetall('remote_update') ?? [];
    foreach ($files as $filename => $contents) { 
        $file = SITE_PATH . '/' . trim($filename, '/');
        io::create_dir(dirname($file));
        file_put_contents($file, base64_decode($contents));
    }
    redis::del('remote_update');

}

/**
 * Delete files
 */
private function delete_files()
{

    // Delete files
    $files = redis::lrange('remote_rm', 0, -1) ?? [];
    foreach ($files as $filename) { 
        $file = SITE_PATH . '/' . trim($filename, '/');

        if (is_dir($file)) { 
            io::remove_dir($file);
        } elseif (file_exists($file)) { 
            unlink($file);
        }
    }
    redis::del('remote_rm');

}

/**
 * Save components
 */
private function save_components()
{

    // Go through components
    $saves = redis::lrange('remote_save', 0, -1) ?? [];
    foreach ($saves as $json) { 
        $vars = json_decode($json, true);

        $client = app::make(remote_access::class);
        $client->save($vars);
    }
    redis::del('remote_save');

}

/**
 * Delete components
 */
private function delete_components()
{

    // Go through components
    $deletes = redis::lrange('remote_delete', 0, -1) ?? [];
    foreach ($deletes as $json) { 
        $vars = json_decode($json, true);

        $client = app::make(remote_access::class);
        $client->delete($vars);
    }
    redis::del('remote_delete');

}

/**
 * Scan packages
 */
private function scan_packages()
{

    // Go through scans
    $scans = redis::lrange('remote_scan', 0, -1) ?? [];
    foreach ($scans as $json) { 
        $vars = json_decode($json, true);

        $client = app::make(remote_access::class);
        $client->scan($vars);
    }
    redis::del('remote_scan');

}

}



