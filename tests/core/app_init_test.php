<?php
declare(strict_types = 1);

namespace tests\core;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\app\tests\test;


/**
 * Simple class that only tests the initialization of the app class, 
 * as all other tests initialize it within the setUp() function, hence it can't be 
 * tested due to 'app' being a singleton.
 */
class test_app_init extends test
{

/**
 * Iniitalize the app
 */
public function test_initialize()
{

    // Create app class
    $app = new app('test');

    // Get instance
    $app = app::get_instance();
    $this->assertNotNull($app);
    $this->assertEquals('test', $app::get_reqtype());

    // Initialize
    $this->invoke_method($app, 'initialize');
    $this->assertTrue(function_exists('fdate'));

}
}


