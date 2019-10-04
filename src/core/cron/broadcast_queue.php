<?php
declare(strict_types = 1);

namespace apex\core\cron;

use apex\app;
use apex\svc\db;
use apex\svc\debug;

/**
 * Class that andles the crontab job.
 */
class broadcast_queue
{

    // Properties
    public $time_interval = 'I30';
    public $name = 'broadcast_queue';

/**
 * Processes the crontab job.
 */
public function process()
{



}

}
