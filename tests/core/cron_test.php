<?php
declare(strict_types = 1);

namespace tests\core;

use apex\app;
use apex\svc\db;
use apex\svc\redis;
use apex\svc\debug;
use apex\svc\components;
use apex\app\tests\test;


/**
 * Handles all unit tests for the crontab jobs located 
 * within the /src/core/cron/ directory.
 */
class test_cron extends test
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
 * tearDown
 */
public function tearDown():void
{

}

/**
 * server_status
 */
public function test_server_check()
{

    // initialize
    redis::del('config:server_status');

    // Process
    $cron = components::call('process', 'cron', 'server_check', 'core');

    // Check
    $vars = redis::hgetall('config:server_status');
    $this->assertIsArray($vars);
    $this->assertArrayHasKey('cpu', $vars);
    $this->assertArrayHasKey('ram', $vars);
    $this->assertArrayHasKey('hd', $vars);

}

/**
 * backup
 */
public function test_backup()
{

    // Initialize
    $cron = components::load('cron', 'backup', 'core');

    // Test backups off
    app::update_config_var('core:backups_enable', 0);
    $this->assertFalse($cron->process());
    app::update_config_var('core:backups_enable', 1);

    // Full backup
    app::update_config_var('core:backups_next_full', 100);
    $filename = $cron->process();
    $this->assertNotEmpty($filename);
    $this->assertFileExists(SITE_PATH . '/storage/backups/' . $filename);
    $this->assertGreaterThan(100, (int) app::_config('core:backups_next_full'));
    // Check database row
    $row = db::get_row("SELECT * FROM internal_backups WHERE filename = %s", $filename);
    $this->assertNotFalse($row);

    // Database only backup
    app::update_config_var('core:backups_next_db', 100);
    $filename = $cron->process();
    $this->assertNotEmpty($filename);
    $this->assertFileExists(SITE_PATH . '/storage/backups/' . $filename);
    $this->assertGreaterThan(100, (int) app::_config('core:backups_next_db'));
    // Check database row
    $row = db::get_row("SELECT * FROM internal_backups WHERE filename = %s", $filename);
    $this->assertNotFalse($row);

    // Get false
    db::query("UPDATE internal_backups SET expire_date = date_sub(now(), interval 1 hour)");
    $ok = $cron->process();
    $this->assertFalse($ok);
    $this->assertFileNotExists($filename);

    // Check database row
    $row = db::get_row("SELECT * FROM internal_backups WHERE filename = %s", $filename);
    $this->assertFalse($ok);

}

/**
 * maintenance
 */
public function test_maintenance()
{

    // Process
    $ok = components::call('process', 'cron', 'maintenance', 'core');
    $this->assertTrue(true);

}

}


