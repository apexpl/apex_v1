<?php
declare(strict_types = 1);

namespace apex\app\io;

use apex\app;
use apex\libc\db;
use apex\libc\redis;
use apex\libc\debug;
use apex\libc\io;
use apex\libc\storage;
use apex\libc\date;
use apex\app\exceptions\ApexException;

/**
 * Handles all backup functionality, including performing the local backups, 
 * plus uploading to the appropriate remote service (AWS, Dropbox, Google 
 * Drive, etc.) 
 */
class backups
{

/**
 * Performs a backup of the system, and stores archive file locally within the 
 * /data/backups/ directory. 
 *
 * @param string $type The type of backup to perform (db or full)
 *
 * @return string The name or the .tar.gz archive that was created.
 */
public function perform_backup(string $type = 'full')
{ 

    // Check if backups enabled
    if (app::_config('core:backups_enable') != 1) { 
        return false;
    }

    // Perform local backup
    $filename = $this->backup_local($type);
    $archive_file = sys_get_temp_dir() . '/' . $filename;

    // Add to remote file storage
    storage::add("backups/$filename", $archive_file);

    // Delete local file, if needed
    if (file_exists($archive_file)) { 
        @unlink($archive_file);
    }

    // Add to database
    $expire_date = date::add_interval(app::_config('core:backups_retain_length'));
    db::insert('internal_backups', array(
        'filename' => $filename, 
        'expire_date' => $expire_date)
    );

    // Return
    return $filename;

}

/**
 * Perform a local backup, and save archive file to /data/backups/ directory 
 *
 * @param string $type The type of backup to perform (db / full)
 *
 * @return string The name of the archive file within /data/backups/ directory
 */
private function backup_local(string $type)
{ 

    // Debug
    debug::add(1, tr("Starting backup of type {1}", $type), 'info');

    // Get database info
    $dbinfo = redis::hgetall('config:db_master');

    // Define .sql dump file
    $sqlfile = SITE_PATH . '/dump.sql';
    if (file_exists($sqlfile)) { @unlink($sqlfile); }

    // Dump mySQL database
    $dump_cmd = "mysqldump -u" . $dbinfo['dbuser'] . " -p" . $dbinfo['dbpass'] . " -h" . $dbinfo['dbhost'] . " -P" . $dbinfo['dbport'] . " " . $dbinfo['dbname'] . " > $sqlfile";
    system($dump_cmd);

    // Get filename
    $filename = $type . '-' . date('Ymd_Hi') . '.tar';
    $archive_file = sys_get_temp_dir() . '/' . $filename;
    chdir(SITE_PATH);

    // Archive the system
    $backup_source = $type == 'db' ? "./dump.sql" : "./";
    $tar_cmd = "tar --exclude='storage/backups/*' -cf $archive_file $backup_source";
    system($tar_cmd);
    system("gzip $archive_file");

    // Delete dump.sql, if exists
    if (file_exists(SITE_PATH . '/dump.sql')) {
        @unlink(SITE_PATH . '/dump.sql');
    }

    // Debug
    debug::add(1, tr("Completed backup of type {1}, located at {2}", $type, $archive_file), 'info');

    // Return
    return $filename . '.gz';

}
}



