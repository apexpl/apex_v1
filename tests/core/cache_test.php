<?php
declare(strict_types = 1);

namespace tests\core;

use apex\app;
use apex\svc\db;
use apex\svc\redis;
use apex\svc\debug;
use apex\app\io\cache;
use apex\app\tests\test;


/**
 * Handles all unit tests for the cache library 
 * located at /src/app/io/cache.php
 */
class test_cache extends test
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
 * Disabled
 */
public function test_disabled()
{

    // Initialize
    app::update_config_var('core:cache', 0);
    $client = new cache();

    // Test
    $this->assertFalse($client->get('test'));
    $this->assertFalse($client->set('test', 'test'));
    $this->assertFalse($client->has('test'));

    app::update_config_var('core:cache', 1);
}

/**
 * Set
 */
public function test_set()
{

    // Initialize
    redis::del('cache:unit_test');
    $client = new cache();

    // Test
    $client->set('unit_test', 'testing 12345');
    $this->assertEquals('testing 12345', $client->get('unit_test'));

}

/**
 * Has
 */
public function test_has()
{

    // Test
    $client = new cache();
    $this->assertFalse($client->has('some_junk_alias_will_never_exist'));
    $this->assertTrue($client->has('unit_test'));

}

/**
 * Get
 */
public function get()
{

    // Test
    $client = new cache();
    $this->assertEquals('test 12345', $client->get('unit test'));
    $this->assertFalse($client->get('some_junk_alias'));

}

/**
 * Delete
 */
public function test_delete()
{

    $client = new cache();
    $this->assertTrue($client->has('unit_test'));

    $client->delete('unit_test');
    $this->assertFalse($client->has('unit_-test'));

}

/**
 * mset
 */
public function test_mset()
{

    // Initialize
    $client = new cache();
    $vars = array(
        'name1' => 'matt', 
        'name2' => 'jason', 
        'name3' => 'luke'
    );

    // Set
    $client->mset($vars);
    foreach ($vars as $key => $value) { 
        $this->assertEquals($value, $client->get($key));
    }

}

/**
 * mget
 */
public function test_mget()
{

    $client = new cache();
    $vars = $client->mget('name1','name2','name3');
    $this->assertCount(3, $vars);
    $this->assertEquals('matt', $vars['name1']);
    $this->assertEquals('jason', $vars['name2']);
    $this->assertEquals('luke', $vars['name3']);

}

/**
 * mdelete
 */
public function mdelete()
{

    $client = new cache();
    $cache->mdelete(array('name1','name3'));
    $this->assertFalse($client->has('name1'));
    $this->assertTrue($client->has('name2'));
    $this->assertFalse($client->has('name3'));

}

/**
 * Clear
 */
public function test_clear()
{

    $client = new cache();
    $client->clear();
    $this->assertFalse($client->has('name2'));

}
}


