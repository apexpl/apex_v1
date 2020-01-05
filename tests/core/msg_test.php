<?php
declare(strict_types = 1);

namespace tests\core;

use apex\app;
use apex\libc\db;
use apex\libc\redis;
use apex\libc\debug;
use apex\libc\date;
use apex\app\msg\utils\msg_utils;
use apex\app\msg\dispatcher;
use apex\app\msg\sms;
use apex\app\msg\emailer;
use apex\app\msg\websocket;
use apex\app\msg\alerts;
use apex\app\msg\objects\event_message;
use apex\app\msg\objects\email_message;
use apex\app\msg\objects\sms_message;
use apex\app\msg\objects\websocket_message;
use apex\app\web\ajax;
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
    app::update_config_var('core:server_type', 'all');
    app::set_uri('index');
    $html = $this->http_request('index');

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
 * event_message -- invalid routing key
 */
public function test_event_message_invalid_routing_key()
{

    // Get exception
    $this->waitException('Invalid routing key');
    $msg = new event_message('junkeventmessage');

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
 * Test false given in direct message
 */
public function test_event_message_direct_false()
{

    // Send message
    $client = app::make(dispatcher::class);
    $msg = new event_message('core.unit_test.direct_false');
    $msg->set_type('direct');
    $response = $client->dispatch($msg);
    $this->assertEquals('fail', $response->get_status());

}

/** 
 * event message -- throw exception
 */
public function test_event_message_exception()
{

    // Wait for exception
    $this->waitException('unit test');

    //  Send message
    $client = new dispatcher();
    $msg = new event_message('core.unit_test.throw_exception');
    $response = $client->dispatch($msg);

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

    // Delete queue
    redis::del('test:websocket_queue');

    // Get AJAX
    $ajax = new ajax();
    $ajax->alert('unit test');
    $msg = new websocket_message($ajax, array('admin:1'));

    // Send websocket message
    $client = new websocket();
    $client->dispatch($msg);
    sleep(1);

    // Check redis queue
    $messages = redis::lrange('test:websocket_queue', 0, -1);
    $this->assertCount(1, $messages);
    $vars = json_decode($messages[0], true);

    // Check message
    $this->assertArrayHasKey('status', $vars);
    $this->assertEquals('ok', $vars['status']);
    $this->assertIsArray($vars['actions']);
    $this->assertCount(1, $vars['actions']);
    $this->assertEquals('unit test', $vars['actions'][0]['message']);

}

/**
 * Alerts - notification
 */
public function test_alerts_dispatch_notification()
{

    // Initialize
    $alerts = app::make(alerts::class);
    redis::del('test:websocket_queue');

    // Send alert
    $alerts->dispatch_notification('admin:1', 'Unit Test Notification', '/admin/unit_test.msg');
    sleep(1);

    // Check redis queue
    $messages = redis::lrange('test:websocket_queue', 0, -1);
    $this->assertCount(1, $messages);
    $vars = json_decode($messages[0], true);

    // Assert message
    $this->assertArrayHasKey('status', $vars);
    $this->assertEquals('ok', $vars['status']);
    $this->assertIsArray($vars['recipients']);
    $this->assertContains('admin:1', $vars['recipients']);

}

/**
 * Alerts - dispatch_message
 */
public function test_alerts_dispatch_message()
{

    // Initialize
    $alerts = app::make(alerts::class);
    redis::del('test:websocket_queue');

    // Dispatch message
    $alerts->dispatch_message('admin:1', 'Matt', 'unit test message', '/admin/unit_test.msg');
    sleep(1);

    // Check redis queue
    $messages = redis::lrange('test:websocket_queue', 0, -1);
    $this->assertCount(1, $messages);
    $vars = json_decode($messages[0], true);

    // Assert message
    $this->assertArrayHasKey('status', $vars);
    $this->assertEquals('ok', $vars['status']);
    $this->assertIsArray($vars['recipients']);
    $this->assertContains('admin:1', $vars['recipients']);

    // Send message to all admin
    redis::del('test_websocket_queue');
    $alerts->dispatch_message('admin', 'Matt', 'unit test message', '/admin/unit_test.msg');
    sleep(1);

    // Check redis queue
    $messages = redis::lrange('test:websocket_queue', 0, -1);
    $this->assertCount(2, $messages);
    $vars = json_decode($messages[0], true);

    // Assert message
    $this->assertArrayHasKey('status', $vars);
    $this->assertEquals('ok', $vars['status']);
    $this->assertIsArray($vars['recipients']);
    $this->assertContains('admin:1', $vars['recipients']);


}

/**
 * E-mail
 */
public function test_email_message()
{

    // Initialize
    app::update_config_var('core:server_type', 'all');

    // Get message
    $msg = app::make(email_message::class);
    $msg->from_email('matt@envrin.com');
    $msg->to_email('unit@test.com');
    $msg->from_name('Matt');
    $msg->to_name('Unit Test');
    $msg->reply_to('reply@test.com');
    $msg->cc('cc@test.com');
    $msg->bcc('bcc@test.com');
    $msg->subject('test subject');
    $msg->message('a test message for the unit test');
    $msg->content_type('text/plain');

    // Assert
    $this->assertEquals('matt@envrin.com', $msg->get_from_email());
    $this->assertEquals('unit@test.com', $msg->get_to_email());
    $this->assertEquals('Matt <matt@envrin.com>', $msg->get_sender_line());
    $this->assertEquals('Unit Test <unit@test.com>', $msg->get_recipient_line());
    $this->assertEquals('test subject', $msg->get_subject());
    $this->assertEquals('a test message for the unit test', $msg->get_message());

    // Format message
    $formatted = $msg->format_message();

    // Add attachment
    $msg->add_attachment('apex', file_get_contents(SITE_PATH . '/public/index.php'));
    $formatted = $msg->format_message();

    // Initialize
    $client = app::make(dispatcher::class);
    $emailer = app::get(emailer::class);
    $emailer->clear_queue();

    // Send message
    $msg = new event_message('core.notify.send_email', $msg);
    $msg->set_type('direct');
    $client->dispatch($msg);

    // Check message queue
    $messages = redis::lrange('test:email_queue', 0, -1);
    $this->assertCount(1, $messages);

}

/**
 * E-Mail Message - invalid sender
 */
public function test_email_message_invalid_sender()
{

    $this->waitException('Invalid e-mail address');
    $msg = app::make(email_message::class);
    $msg->from_email('someinvalidemail');

}

/**
 * E-Mail Message - invalid recipient
 */
public function test_email_message_invalid_recipient()
{

    $this->waitException('Invalid e-mail address');
    $msg = app::make(email_message::class);
    $msg->to_email('someinvalidemail');

}

/**
 * E-Mail Message - invalid reply_to
 */
public function test_email_message_invalid_reply_to()
{

    $this->waitException('Invalid e-mail address');
    $msg = app::make(email_message::class);
    $msg->reply_to('someinvalidemail');

}

/**
 * E-Mail Message - invalid cc
 */
public function test_email_message_invalid_cc()
{

    $this->waitException('Invalid e-mail address');
    $msg = app::make(email_message::class);
    $msg->cc('someinvalidemail');

}

/**
 * E-Mail Message - invalid bcc
 */
public function test_email_message_invalid_bcc()
{

    $this->waitException('Invalid e-mail address');
    $msg = app::make(email_message::class);
    $msg->bcc('someinvalidemail');

}

/**
 * E-mail message - invalid content type
 */
public function test_email_message_invalid_conent_type()
{

    // Get exception
    $this->waitException('Invalid content-type specified');
    $msg = app::make(email_message::class);
    $msg->content_type('somejunktype');

}

/**
 * RabbitMQ Connection -- Invalid
 */
public function test_rabbitmq_connection_invalid()
{

    // Set vars
    $vars = array(
        'host' => 'localhost', 
        'user' => 'some_user', 
        'pass' => 'some_junk_invalid_pass', 
        'port' => 1234
    );

    // Set wrong connection info
    redis::hmset('config:rabbitmq', $vars);
    $this->expectException(\PhpAmqpLib\Exception\AMQPIOException::class);
    
    // Throw exception
    $utils = app::make(msg_utils::class);
    $utils->get_rabbitmq_connection();

}

/**
 * Get RabbitMQ connection
 */
public function test_rabbitmq_connection()
{

    // Get connection vars
    $utils = app::make(msg_utils::class);
    redis::del('config:rabbitmq');
    $vars = $utils->get_rabbitmq_connection_info();
    $this->assertIsArray($vars);
    $this->assertArrayHasKey('host', $vars);
    $this->assertArrayHasKey('port', $vars);
    $this->assertArrayHasKey('user', $vars);
    $this->assertArrayHasKey('pass', $vars);

    // Set vars
    redis::hmset('config:rabbitmq', $vars);
    $vars = $utils->get_rabbitmq_connection_info();
    $this->assertIsArray($vars);
    $this->assertArrayHasKey('host', $vars);
    $this->assertArrayHasKey('port', $vars);
    $this->assertArrayHasKey('user', $vars);
    $this->assertArrayHasKey('pass', $vars);

    // Get connection
    $conn = $utils->get_rabbitmq_connection();
    $this->assertNotNull($conn);

    // Get connection again, to get existing connection
    $conn = $utils->get_rabbitmq_connection();
    $this->assertNotNull($conn);

    // Get listenders
    $listeners = $utils->get_listeners('core.notify.ws_send');
    $this->assertIsArray($listeners);

}

/**
 * RPC commands, changing app:: and view:: methods
 */
public function test_rpc()
{

    // Initialize
    app::update_config_var('core:server_type', 'web');
    $client = app::make(dispatcher::class);

    // Initialize
    app::set_area('public');
    app::set_theme('koupon');
    app::set_uri('index');
    app::set_userid(0);

// Set area / uri / userid
    $msg = new event_message('core.unit_test.set_area');
    $response = $client->dispatch($msg)->get_response('core');
    $this->assertEquals('area_test', $response);

    // Assert
    $this->assertEquals('members', app::get_area());
    $this->assertEquals('limitless', app::get_theme());
    $this->assertEquals('members/account/update_profile', app::get_uri());
    $this->assertEquals(1, app::get_userid());
    $this->assertEquals('test123', app::_cookie('unit_test'));
    $this->assertEquals(404, app::get_res_status());
    $this->assertEquals('application/json', app::get_res_content_type());

    // Send direct message
    $msg = new event_message('core.unit_test.dispatch_direct');
    $msg->set_type('direct');
    //$response = $client->dispatch($msg)->get_response('core');
    //$this->assertTrue($response);

    // Wait for exception
    $this->waitException('unit test');
    $client = new dispatcher();
    $msg = new event_message('core.unit_test.throw_exception');
    $response = $client->dispatch($msg);

}

/**
 * Clean up
 */
public function test_cleanup()
{

    app::update_config_var('core:server_type', 'all');
    $this->assertTrue(true);

}


}


