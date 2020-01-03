<?php
declare(strict_types = 1);

namespace tests\core;

use apex\app;
use apex\libc\db;
use apex\libc\debug;
use apex\libc\redis;
use apex\app\utils\hashes;
use apex\app\tests\test;


/**
 * Handles unit tests for the library at 
 * /src/app/utils/hashes.php
 */
class test_hashes extends test
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
 * create_options - Invalid alias
 */
public function test_create_options_invalid_alias()
{

    $client = new hashes();
    $this->waitException('does not exist');
    $client->create_options('core:some_junk_hash_that_will_never_exist');

}

/**
 * create_options - no redis
 */
public function test_create_options_no_redis()
{

    // Delete from redis
    redis::hdel('hash', 'core:boolean');
    $client = new hashes();

    // Get exception
    $this->waitException('The hash does not exist within redis');
    $client->create_options('core:boolean');

}

/**
 * Reset redis hash
 */
public function test_reset_redis_boolean_hash()
{

    $vars = array(
        0 => 'No', 
        1 => 'Yes'
    );
    redis::hset('hashes', 'core:boolean', json_encode($vars));
    $this->assertTrue(true);

}

/**
 * Create options - radio / checkbox
 */
public function test_create_options_radio_checkbox()
{

    // Add boolean to redis
    $vars = array(0 => 'No', 1 => 'Yes');
    redis::hset('hash', 'core:boolean', json_encode($vars));

    // Create checkbox
    $client = new hashes();
    $options = $client->create_options('core:boolean', 1, 'checkbox', 'boolean');
    $this->assertNotEmpty($options);
    $this->assertStringContains($options, 'checkbox');
    $this->assertStringContains($options, 'boolean');

    // Radio
    $options = $client->create_options('core:boolean', 1, 'radio', 'boolean');
    $this->assertNotEmpty($options);
    $this->assertStringContains($options, 'radio');
    $this->assertStringContains($options, 'boolean');

}

/** 
 * get_hash_var
 */
public function test_get_hash_var()
{

    // Initialize
    $client = new hashes();
    $data = redis::hget('hash', 'core:form_fields');
    redis::hdel('hash', 'core:form_fields');

    // Get false
    $ok = $client->get_hash_var('core:form_fields', 'textbox');
    $this->assertFalse($ok);

    // Invalid JSON false
    redis::hset('hash', 'core:form_fields', 'invalid json format');
    $ok = $client->get_hash_var('core:form_fields', 'select');
    $this->assertFalse($ok);
    redis::hset('hash', 'core:form_fields', $data);

    // Invalid option
    $ok = $client->get_hash_var('core:form_fields', 'asdfasdgsa');
    $this->assertFalse($ok);

    // Get select
    $var = $client->get_hash_var('core:form_fields', 'select');
    $this->assertEquals('Select List', $var);

    //Get exception
    $this->waitException('does not exist');
    $client->get_hash_var('core:sklsdfsgsd', 'test');

}

/**
 * Create currency options
 */
public function test_create_options_currency()
{

    $client = new hashes();
    $options = $client->parse_data_source('stdlist:currency:USD');
    $this->assertNotEmpty($options);

}

}






