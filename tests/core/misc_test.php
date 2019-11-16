<?php
declare(strict_types = 1);

namespace tests\core;

use apex\app;
use apex\svc\db;
use apex\svc\redis;
use apex\svc\debug;
use apex\svc\components;
use apex\app\db\db_connections;
use apex\app\tests\test;


/**
 * Handles various misc tests that don't warrant a class of their own, 
 * and is used to help obtain 100% code coverage.
 */
class test_misc extends test
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
 * Test invalid timezone.
 */
public function test_app_invalid_timezone()
{

    $vars = app::get_tzdata('junk123');
    $this->assertEquals(0, (int) $vars[0]);
    $this->assertEquals(0, (int) $vars[1]);

}

/**
 * Test app::get_currency()
 */
public function test_app_get_currency()
{

    // Get CAD
    $vars = app::get_currency_data('CAD');
    $this->assertArrayHasKey('decimals', $vars);
    $this->assertArrayHasKey('symbol', $vars);
    $this->assertArrayHasKey('is_crypto', $vars);
    $this->assertEquals(2, (int) $vars['decimals']);
    $this->assertEquals(0, (int) $vars['is_crypto']);

    // Check crypto
    redis::sadd('config:crypto_currency', 'UST');
    $vars = app::get_currency_data('UST');
    $this->assertEquals(8, (int) $vars['decimals']);
    $this->assertEquals(1, (int) $vars['is_crypto']);
    redis::srem('config:crypto_currency', 'UST');

    // Trigger exception
    $this->waitException('Currency does not exist');
    app::get_currency_data('UST');

}

/**
 * app::set_userid()
 */
public function test_app_set_userid()
{

    $userid = app::get_userid();
    app::set_userid(485);

    $this->assertEquals(485, app::get_userid());
    app::set_userid($userid);

}

/**
 * app::set_area()
 */
public function test_app_set_area()
{

    // Change to members
    app::set_area('members');
    $this->assertEquals('members', app::get_area());
    $this->assertEquals(app::_config('users:theme_members'), app::get_theme());

    // Change to public
    app::set_area('public');
    $this->assertEquals('public', app::get_area());
    $this->assertEquals(app::_config('core:theme_public'), app::get_theme());

    // Set invalid area
    $this->waitException('Invalid area specified');
    app::set_area('junk');

}

/**
    * app::set_urio() -- Exception
 */
public function test_app_set_uri_exception()
{

    // Wait for exception
    $this->waitException('Invalid URI specified');
    app::set_uri('/somedir/ ^id/page');

}

/**
 * app::getall_request_vars()
 */
public function test_app_getall_request_vars()
{

    // Get vars
    $vars = app::getall_request_vars();
    $this->assertArrayHasKey('host', $vars);
    $this->assertArrayHasKey('port', $vars);
    $this->assertArrayHasKey('method', $vars);
    $this->assertArrayHasKey('content_type', $vars);
    $this->assertArrayHasKey('uri', $vars);
    $this->assertArrayHasKey('ip_address', $vars);
    $this->assertArrayHasKey('userid', $vars);
    $this->assertArrayHasKey('user_agent', $vars);
    $this->assertArrayHasKey('area', $vars);

}

/**
 * App header functions
 */
public function test_app_http_headers()
{


    // Has header
    $this->assertFalse(app::has_header('some_junk_header'));

}

/**
 * Table -- core/table/auth_history -- with 'userid' variable
 */
public function test_core_table_auth_history()
{

    // Load components
    $data = array('userid' => 1, 'type' => 'admin');
    $table = components::load('table', 'auth_history', 'core', '', $data);
    $table->get_attributes($data);

    // Get total
    $total = $table->get_total();
    $this->assertNotFalse($total);

    // Get rows
    $rows = $table->get_rows();
    $this->assertNotFalse($rows);

}


}


