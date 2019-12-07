<?php
declare(strict_types = 1);

namespace apex\core\worker;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\svc\date;
use apex\app\interfaces\msg\EventMessageInterface as event;
use apex\app\tests\test;

/**
 * Handles unit test functions
 */
class unit_test extends test
{

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



}

