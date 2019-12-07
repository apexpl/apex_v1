<?php
declare(strict_types = 1);

namespace tests\core;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\app\tests\test;


/**
 * Add any necessary phpUnit test methods into this class.  You may execute all 
 * tests by running:  php apex.php test core
 */
class test_pkg extends test
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



}

