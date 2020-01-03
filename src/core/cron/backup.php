<?php
declare(strict_types = 1);

namespace apex\core\cron;

use apex\app;
use apex\libc\db;
use apex\libc\date;
use apex\libc\storage;
use apex\app\io\backups;


/**
 * Small crontab job that handles the automated backups utilizing the library 
 * at /src/core/backups.php. 
 */
class backup 
{

    // Properties
    public $time_interval = 'H3';
    public $name = 'Backup Database';

/**
 * Processes the crontab job. 
 */
public function process()
{ 

    // If not backups enabled
    if (app::_config('core:backups_enable') != 1) { 
        return false;
    }

    // Perform needed backups
    $client = app::make(backups::class);
    if (time() >= app::_config('core:backups_next_full')) { 
        $filename = $client->perform_backup('full');
        app::update_config_var('core:backups_next_full', date::add_interval(app::_config('core:backups_full_interval'), '', false));

    } elseif (time() >= app::_config('core:backups_next_db')) { 
        $filename = $client->perform_backup('db'); 
        app::update_config_var('core:backups_next_db', date::add_interval(app::_config('core:backups_db_interval'), '', false));

    } else { 
        $filename = false;
    }

    // Delete expired backups
    $rows = db::query("SELECT * FROM internal_backups WHERE expire_date < now() ORDER BY id");
    foreach ($rows as $row) {
        storage::delete('backups/' . $row['filename']);
        db::query("DELETE FROM internal_backups WHERE id = %i", $row['id']);
    }

    // Return
    return $filename;


}


}

