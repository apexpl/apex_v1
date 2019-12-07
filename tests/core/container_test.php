<?php
declare(strict_types = 1);

namespace tests\core;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\svc\components;
use apex\core\admin;
use apex\app\msg\emailer;
use apex\app\tests\test_emailer;
use apex\app\tests\test;


/**
 * Handles unit tests for the http container, located 
 * at /src/app/sys/container.php file.
 */
class test_container extends test
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
 * Build container
 */
public function test_build_container()
{

    app::build_container('test');
    $this->assertTrue(true);

}

/**
 * Test get
 */
public function test_get()
{

    $obj = app::make(test_emailer::class);
    $app::set(emailer::class, $obj);

    $chk = app::get(emailer::class);
    $this->assertInstanceOf(test_emailer::class, $chk);

    // Get false
    $this->waitException('Unable to determine full class name');
    app::get('testjunk');

}

/**
 * Invalid param type
 */
public function test_invalid_param_type()
{

    $this->waitException('Invalid type for the parameter name');
    app::make(admin::class, ['id' => app::get_instance()]);

}

/**
 * Optional aparmas
 */
public function test_optional_param()
{

    $vars = array(
        'data' => array('table' => 'core:admin')
    );
    $html = components::call('process', 'htmlfunc', 'display_table', 'core', '', $data);
    $this->assertTrue(true);

    // Get exception
    $this->waitException('Unable to find value');
    $html = components::call('process', 'htmlfunc', 'display_table', 'core', '');

}

/**
 * Test param types
 */
public function test_param_types9)
{

// Test different types
    app::call([test_container::class, 'string_test'], ['name' => 'matt']);
    app::call([test_container::class, 'float_test'], ['amount' => 53.15]);
    app::call([test_container::class, integer_test'], ['num' => 257]);
    app::call([test_container::class, 'boolean_test'], ['ok' => true]);
    $this->assertTrue(true);

}


}


