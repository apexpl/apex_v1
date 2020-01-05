<?php
declare(strict_types = 1);

namespace apex\core\worker;

use apex\app;
use apex\libc\db;
use apex\libc\debug;
use apex\libc\date;
use apex\libc\view;
use apex\app\interfaces\msg\EventMessageInterface as event;
use apex\app\tests\test;
use apex\app\exceptions\ApexException;

/**
 * Handles unit test functions
 */
class unit_test extends test
{

    // Routing key
    public $routing_key = 'core.unit_test';

/**
 * Disptach RPC call
 *
 * @param EventMessageInterface $msg The message that was dispatched.
 */
public function dispatch(event $msg)
{

    // Check message
    $this->assertIsObject($msg);
    $this->assertEquals('rpc', $msg->get_type());
    $this->assertEquals('core.unit_test.dispatch', $msg->get_routing_key(true));
    $this->assertEquals('core.unit_test', $msg->get_routing_key());
    $this->assertEquals('dispatch', $msg->get_function());

    // Check params
    list($chk_date, $chk_num) = $msg->get_params();
    $this->assertEquals(date('Y-m-d'), $chk_date);
    $this->assertEquals((int) $chk_num, 93827592);

    // Check caller
    $caller = $msg->get_caller();
    $this->assertIsArray($caller);
    $this->assertGreaterThan(45, (int) $caller['line']);
    $this->assertEquals('test_dispatch_msg', $caller['function']);

    // Get request
    $request = $msg->get_request();
    $this->assertIsArray($request);
    $this->assertEquals('public', $request['area']);
    $this->assertEquals('index', $request['uri']);

    // Set response
    $response = array(
        'name' => 'Apex unit test', 
        'date' => date::subtract_interval('M1')
    );

    // Return
    return $response;

}

/**
 * Test event message - direct
 *
 * @param EventMessageInterface $msg The message that was dispatched.
 */
public function dispatch_direct(event $msg)
{

    // Return
    return true;

}

/**
 * Direct false
 *
 * @param EventMessageInterface $msg The message that was dispatched.
 */
public function direct_false()
{
    return false;
}

/**
 * Exception
 */
public function throw_exception()
{
    throw new ApexException('error', 'unit test');
}

/**
 * set_area
 *
 * @param EventMessageInterface $msg The message that was dispatched.
 */
public function set_area(event $msg)
{

    // Get params
    $vars = $msg->get_params();

    // Return
    app::set_area('members');
    app::set_uri('members/account/update_profile');
    app::set_userid(1);
    app::set_theme('limitless');

    // Additional
    app::set_cookie('unit_test', 'test123');
    app::set_res_http_status(404);
    app::set_res_content_type('application/json');
    app::set_res_header('Unit-Test', 'test12345');

    // Views
    view::assign('unit_test', 'was here');
    view::add_callout('success', 'unit test');

    // Return
    return 'area_test';

}

}


