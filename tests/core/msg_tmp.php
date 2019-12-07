<?php
declare(strict_types = 1);

namespace tests\core;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\svc\date;
use apex\app\msg\dispatcher;
use apex\app\msg\sms;
use apex\app\msg\websocket;
use apex\app\msg\alerts;
use apex\app\msg\objects\event_message;
use apex\app\msg\objects\sms_message;
use apex\app\msg\objects\websocket_message;
use apex\app\tests\test;


/**
 * Handles all messaging unit tests, including event dispatches / listeners, 
 * sending of e-mails, SMS messages, and web socket messages, etc.
 */
class test_msg extends test
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
 * Dispatch an event message
 */
public function test_dispatch_msg()
{

    // Initialize
    $date = date('Y-m-d');
    $rand_num = 93827592;

    // Create message
    $msg = new event_message('core.unit_test.dispatch', $date, $rand_num);
    $this->check_event_message_format($msg, $date);

    // Dispatch the message
    $client = new dispatcher();
    $response = $client->dispatch($msg);
    $this->assertIsObject($response);
    $this->check_event_message_format($response, $date);
    $this->assertEquals('ok', $response->get_status());

    // Check response
    $res = $response->get_response('core');
    $this->assertIsArray($res);
    $this->assertEquals('Apex unit test', $res['name']);
    $this->assertEquals(date::subtract_interval('M1'), $res['date']);

    // Get called
    $called = $response->get_called();
    $this->assertIsArray($called);
    $this->assertCount(1, $called);
    $this->assertEquals("apex\\core\\worker\\unit_test", $called[0][0]);
    $this->assertEquals('dispatch', $called[0][1]);

    // Set to direct message
    $msg->set_type('direct');
    $this->assertEquals('direct', $msg->get_type());

    // Send direct message
    $msg = new event_message('core.unit_test.dispatch_direct');
    $msg->set_type('direct');
    $response = $client->dispatch($msg)->get_response('core');
    $this->assertTrue($response);

}

/**
 * Set event message type - invalid
 */
public function test_event_message_set_type_invalid()
{

    // Initialize
    $msg = new event_message('core.unit_test.dispatch');

    // Wait for exception
    $this->waitException('Invalid event message type');
    $msg->set_type('junk');

}

/**
 * Check message format / caller variables
 */
private function check_event_message_format($msg, $date, $chk_type = 'rpc')
{

    // Initial checks
    $this->assertIsObject($msg);
    $this->assertEquals('rpc', $msg->get_type());
    $this->assertEquals('core.unit_test.dispatch', $msg->get_routing_key(true));
    $this->assertEquals('core.unit_test', $msg->get_routing_key());
    $this->assertEquals('dispatch', $msg->get_function());

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

    // Get params
    list($chk_date, $chk_num) = $msg->get_params();
    $this->assertEquals($chk_date, $date);
    $this->assertEquals((int) $chk_num, (int) 93827592);

}

/**
 * Test SMS message
 */
public function test_sms()
{

    // Set variables
    $phone = '1 6045551234';
    $message = 'unit test';
    $from_name = 'Unit Test';

    // Get SMS message
    $sms = new sms_message($phone, $message, $from_name);
    $this->assertIsObject($sms);
    $this->assertEquals($phone, $sms->get_phone());
    $this->assertEquals($message, $sms->get_message());
    $this->assertEquals($from_name, $sms->get_from_name());

    // Dispatch message
    $client = app::make(sms::class);
    $response = $client->dispatch($sms);
    $this->assertTrue(true);

}

/**
 * Web Socket message
 */
public function test_websocket()
{


}

}

