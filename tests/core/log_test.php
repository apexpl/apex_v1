<?php
declare(strict_types = 1);

namespace tests\core;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\svc\io;
use apex\app\sys\log;
use apex\app\tests\test;


/**
 * Test the log handler at /src/app/sys/log.php.  Ensures 
 * it's writing properly formatted lines to the correct files.
 */
class test_log extends test
{

/**
 * setUp
 */
public function setUp():void
{

    // Get app
    if (!$app = app::get_instance()) { 
        $app = new app('test');
    }

}

/** 
 * Add log lines
 */
public function test_write_logs()
{

if (!$app = app::get_instance()) { 
        $app = new app('test');
    }
    $app = new app('test');
    $client = new log();

    // Set levels
    $logdir = SITE_PATH . '/storage/logs';
    $levels = array('debug','info','warning','notice','error','alert','critical','emergency');
    $config_levels = array(
        'info,warning,notice,error,critical,alert,emergency', 
        'notice,error,critical,alert,emergency', 
        'error,critical,alert,emergency', 
        'none'
    );

    // Remove existing log files
    io::remove_dir($logdir);

    // Go through config levels
    $num=1;
    foreach ($config_levels as $config_level) { 

        // Set config levels
        $clevels = explode(",", $config_level);
        $client->set_log_levels($clevels);

        // Go through log levels
        foreach ($levels as $level) {

            // Add log line
            $client->$level("Testing $level with {1} at num $num", array('John'));
            $chk = "Testing $level with John at num $num";

            // Check level log file
            $logfile = $logdir . '/' . $level . '.log';
            if (in_array($level, $clevels) || $level == 'debug') { 
                $this->assertFileContains("$logdir/messages.log", $chk);
                $this->assertFileContains($logfile, $chk);
            } else {
                $this->assertFileNotContains("$logdir/messages.log", $chk);
                $this->assertFileNotContains($logfile, $chk);
            }
        }

        $num++;
    }

}

}

