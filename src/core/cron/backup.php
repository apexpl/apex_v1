<?php
declare(strict_types = 1);

namespace apex\core\cron;

use apex\app;
use apex\core\backups;


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
    if (app::_config('core:backups_enable') != 1) { return; }

    // Perform needed backups
    $client = new backups();
    if (time() >= app::_config('core:backups_next_full')) { $client->perform_backup('full'); }
    elseif (time() >= app::_config('core:backups_next_db')) { $client->perform_backup('db'); }


}


}

