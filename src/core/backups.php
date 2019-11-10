<?php
declare(strict_types = 1);

namespace apex\core;

use apex\DB;
use apex\registry;
use apex\log;
use apex\debug;
use apex\ApexException;
use apex\core\io;
use Kunnu\Dropbox\Dropbox;
use Kunnu\Dropbox\DropboxApp;
use Kunnu\Dropbox\DropboxFile;
use Kunnu\Dropbox\Exceptions\DropboxClientException;


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
    if (registry::config('core:backups_enabled') != 1) { 
        return false;
    }

    // Perform local backup
    $archive_file = $this->backup_local($type);

    // Upload to remote server, if needed
    if (registry::config('backups_remote_service') == 'aws') { 
        $this->upload_aws($archive_file);
    } elseif (registry::config('backups_remote_service') == 'dropbox') { 
        $this->upload_dropbox($archive_file);
    } elseif (registry::config('backups_remote_service') == 'google_drive') { 
        $this->upload_google_drive($archive_file);
    }

    // Delete local file, if needed
    if (registry::config('core:backups_save_locally') != 1) { 
        @unlink(SITE_PATH . '/data/backups/' . $archive_file);
    }

    // Return
    return $archive_file;

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

    // Get database info
    $dbinfo = registry::$redis->hgetall('config:db_master');

    // Define .sql dump file
    $sqlfile = SITE_PATH . '/dump.sql';
    if (file_exists($sqlfile)) { @unlink($sqlfile); }

    // Dump mySQL database
    $dump_cmd = "mysqldump -u" . $dbinfo['dbuser'] . " -p" . $dbinfo['dbpass'] . " -h" . $dbinfo['dbhost'] . " -P" . $dbinfo['dbport'] . " " . $dbinfo['dbname'] . " > $sqlfile";
    system($dump_cmd);

    // Get filename
    $secs = (date('H') * 3600) + (date('i') * 60) + date('s');
    $archive_file = $type . '-' . date('Y-m-d_') . $secs . '.tar';
    io::create_dir(SITE_PATH . '/data/backups');
    chdir(SITE_PATH);

    // Archive the system
    $backup_source = $type == 'db' ? "./dump.sql" : "./";
    $tar_cmd = "tar --exclude='data/backups/*' -cf " . SITE_PATH . '/data/backups/' . $archive_file . " $backup_source";
    system($tar_cmd);
    system("gzip " . SITE_PATH . "/data/backups/$archive_file");
    // Return
    return $archive_file . '.gz';

}
}



