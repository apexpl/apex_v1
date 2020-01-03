<?php
declare(strict_types = 1);

namespace apex\core\cron;

use apex\app;
use apex\libc\db;
use apex\libc\debug;
use apex\libc\io;
use apex\libc\date;
use apex\app\msg\emailer;


/**
 * Handles general core system maintenance including, rotate log files, delete 
 * old logged session data, check for modifications to the filesystem, etc. 
 */
class maintenance 
{




    // Properties
    public $time_interval = 'D1';
    public $name = 'Core System Maintenance';

/**
 * Process the crontab job 
 */
public function process()
{ 

    // Rotate log files
    $this->rotate_log_files();

    // Delete expired session info
    $this->delete_expired_session_logs();

    // Check file hash
    $this->check_file_hash();

}

/**
 * Rotate log files 
 */
private function rotate_log_files()
{ 

    // Debug
    debug::add(3, "Starting to rotate log files");

    // Set variables
    $logdir = SITE_PATH . '/storage/logs/';

    // Delete older files
    $files = io::parse_dir($logdir, false);
    foreach ($files as $file) {
        if (preg_match("/^(.+)\.(\d+)$/", $file, $match) && (int) $match[1] >= 4) { 
            @unlink("$logdir/$file");
        }
    }

// GO through log types
    $types = array('access', 'system', 'messages', 'debug', 'info', 'warning', 'notice', 'error', 'alert', 'critical', 'emergency');
    foreach ($types as $type) {

        // Check file size 
        $file = $logdir . '/' . $file . '.log';
        if (!file_exists($file)) { continue; }
        if (filesize($file) < (app::_config('core:max_logfile_size') * 1000000)) { continue; }

        // Rename old files
        if (file_exists($file . '.3')) { rename($file . '.3', $file . '.4'); }
        if (file_exists($file . '.2')) { rename($file . '.2', $file . '.3'); }
        if (file_exists($file . '.2')) { rename($file . '.2', $file . '.3'); }
        if (file_exists($file . '.1')) { rename($file . '.1', $file . '.2'); }
        rename($file, $file . '.1');
        file_put_contents($file);
    }

    debug::add(2, tr("Successfully completed rotating log files"), 'info');

}

/**
 * Delete expired session logs / info 
 */
private function delete_expired_session_logs()
{ 

    // Debug
    debug::add(3, "Starting to delete expired session logs", 'info');

    // Delete admin logs
    $start_date = date::subtract_interval(app::_config('core:session_retain_logs'));
    db::query("DELETE FROM auth_history WHERE type = 'admin' AND date_added < %dt", $start_date);

    // Delete from users, if needed
    if (app::_config('users:session_retain_logs')) { 
        $start_date = date::subtract_interval(app::_config('users:session_retain_logs'));
        db::query("DELETE FROM auth_history WHERE type = 'user' AND date_added < $dt", $start_date);
    }

    // Debug
    debug::add(2, "Successfully deleted all expired session log data", 'info');

}

/**
 * Check files hash for modifications. 
 *
 * Checks all files hashes within the system to see if any have been modified, 
 * added or deleted without permission.  E-mails the administrators of any 
 * changes to the file system. 
 */
private function check_file_hash()
{ 

    // Initialize
    $added = array();
    $modified = array();
    $deleted = array();

    // Get file hash, and all existing files
    $file_hash = db::get_hash("SELECT filename, file_hash FROM internal_file_hashes WHERE is_system = 1");
    $files = io::parse_dir(SITE_PATH);

    // GO through all files
    foreach ($files as $file) { 
        if (preg_match("/^storage\//", $file)) { continue; }

        // Check SHA1 hash of file
        $chk_hash = sha1_file(SITE_PATH . '/' . $file);
        if (!isset($file_hash[$file])) { 
            $added[] = $file;

            db::insert('internal_file_hashes', array(
                'is_system' => 1,
                'filename' => $file,
                'file_hash' => $chk_hash)
            );

        } elseif ($chk_hash != $file_hash[$file]) { 
            $modified[] = $file;
            db::query("UPDATE internal_file_hashes SET file_hash = %s WHERE filename = %s AND is_system = 1", $chk_hash, $file);
        }

    }

    // Check for deleted files
    foreach ($file_hash as $filename => $hash) { 
        if (in_array($filename, $files)) { continue; }
        $deleted[] = $filename;
    }

    // Return if needed
    if (count($file_hash) == 0) { return; }
    if (count($added) == 0 && count($modified) == 0 && count($deleted) == 0) { return; }

    // Set message
    $subject = "{WARNING] Filesystem Modified (" . app::_config('core:domain_name') . ")";
    $message = "\n\nOne or more files were modified within the system at " . app::_config('core:domain_name') . " as of " . fdate(date('Y-m-d H:i:s'), true) . ".  Below lists all files that were modified.\n\n";
    if (count($added) > 0) { $message .= "--------------------\n-- Files Added\n--------------------\n\n" . implode("\n", $added) . "\n\n"; }
    if (count($modified) > 0) { $message .= "--------------------\n-- Files Modified\n--------------------\n\n" . implode("\n", $modified) . "\n\n"; }
    if (count($deleted) > 0) { $message .= "--------------------\n-- Files Deleted\n--------------------\n\n" . implode("\n", $deleted) . "\n\n"; }
    $message .= "-- END --\n\n";

    // Send e-mails as needed
    $from_email = db::get_field("SELECT email FROM admin ORDER BY id LIMIT 0,1");
    $rows = db::query("SELECT * FROM admin ORDER BY id");
    foreach ($rows as $row) {
        $emailer = app::make(emailer::class); 
        $emailer->send($row['email'], $row['full_name'], $from_email, 'Apex', $subject, $message);
    }

    // Debug
    debug::add(2, "Successfully completed check of all system file hashes for modifications", 'info');


}


}

