<?php
declare(strict_types = 1);

namespace tests\core;

use apex\app;
use apex\libc\db;
use apex\libc\debug;
use apex\libc\components;
use apex\app\io\cache;
use apex\app\interfaces\CacheInterface;
use apex\core\admin;
use apex\app\msg\emailer;
use apex\app\tests\test_container as tcontainer;
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

    $app = app::get_instance();
    $app->build_container('test');
    $this->assertTrue(true);

}

/**
 * Test get
 */
public function test_get()
{

    $obj = app::make(test_emailer::class);
    app::set(emailer::class, $obj);

    // Get cache
    app::makeset(cache::class);
    $cache = app::get(CacheInterface::class);

    $chk = app::get(emailer::class);
    $this->assertInstanceOf(test_emailer::class, $chk);

    // Get false
    $this->waitException('Unable to determine full class name');
    app::get('testjunk');

}

/**
 * make -- no class name
 */
public function test_make_no_class_name()
{

    $this->waitException('Unable to determine full class name');
    app::make('junk_class');

}

/**
 * Invalid param type
 */
public function test_invalid_param_type()
{

    $this->waitException('Invalid type for the parameter');
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
    $html = components::call('process', 'htmlfunc', 'display_table', 'core', '', $vars);
    $this->assertTrue(true);

    // Get exception
    $this->waitException('Unable to find value');
    $html = components::call('process', 'htmlfunc', 'display_table', 'core', '');

}

/**
 * Test param types
 */
public function test_param_types()
{

    $user = app::make(admin::class);

// Test different types
    app::call([tcontainer::class, 'string_test'], ['name' => 'matt']);
    app::call([tcontainer::class, 'float_test'], ['amount' => 53.15]);
    app::call([tcontainer::class, 'integer_test'], ['num' => 257]);
    app::call([tcontainer::class, 'boolean_test'], ['ok' => true]);
    app::call([tcontainer::class, 'object_test'], ['user' => $user]);
    app::call([tcontainer::class, 'instanceof_test'], ['admin' => $user]);

    // Check null
    $value = app::call([tcontainer::class, 'null_test']);
    $this->assertNull($value);

}

}


