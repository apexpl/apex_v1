<?php
declare(strict_types = 1);

namespace tests\core;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\app\sys\auth;
use apex\app\tests\test;

/** 
 * Handles all unit tests for the authentication library 
 * at /src/app/sys/auth.php
 */
class test_auth extends test
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




}

