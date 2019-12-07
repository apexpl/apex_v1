<?php
declare(strict_types = 1);

namespace tests\core;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\core\notification;
use apex\app\tests\test;



/**
 * Handles various unit tests for the 
 * class located at /src/core/notification.php.
 */
class test_notification extends test
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
 * Notification - merge_fields - invalid controller
 */
public function test_notification_get_merge_fields_invalid_controller()
{

    // Get exception
    $this->waitException('does not exist');
    $client = app::make(notification::class);
    $client->get_merge_fields('some_invalid_junk_controller');

}

/**
 * Notification - merge_vars - invalid controller
 */
public function test_notification_get_merge_vars_invalid_controller()
{

    // Get exception
    $this->waitException('does not exist');
    $client = app::make(notification::class);
    $client->get_merge_vars('some_invalid_junk_controller');

}

/**
 * Send - notification not exist
 */
public function test_send_not_exists()
{

    $this->waitException('Notification does not exist');
    $client = app::make(notification::class);
    $client->send(1, 83858733, array());

}

/**
 * Create - no controller defined
 */
public function test_create_controller_undefined()
{

    $this->waitException('No notification controller was defined');
    $client = app::make(notification::class);
    $client->create(array());

}

/**
 * Create - no sender defined
 */
public function test_create_sender_undefined()
{

    $this->waitException('No sender variable');
    $client = app::make(notification::class);
    $client->create(array('controller' => 'system'));

}

/**
 * Create - no recipient defined
 */
public function test_create_recipient_undefined()
{

    $this->waitException('No recipient variable');
    $client = app::make(notification::class);

    // Create
    $vars = array(
        'controller' => 'system', 
        'sender' => 'admin:1'
    );
    $client->create($vars);

}

/**
 * create - invalid controller
 */
public function test_create_invalid_controller()
{

    $this->waitException('does not exist');
    $client = app::make(notification::class);

    // Set vars
    $vars = array(
        'controller' => 'some_junk_invalid_controller', 
        'sender' => 'admin:1', 
        'recipient' => 'user'
    );
    $client->create($vars);

}

/**
 * edit -- notification not exist
 */
public function test_edit_not_exists()
{

    $this->waitException('Notification does not exist');
    $client = app::make(notification::class);
    $client->edit(3847275);

}

/**
 * delete
 */
public function test_delete()
{

    // Set vars
    $vars = array(
        'controller' => 'system', 
        'sender' => 'admin:1', 
        'recipient' => 'user', 
        'cond_action' => '2fa', 
        'content_type' => 'text/plain', 
        'subject' => 'unit_test_delete', 
        'contents' => 'test'
    );

    // Create
    $client = app::make(notification::class);
    $notification_id = $client->create($vars);
    $this->assertNotEmpty($notification_id);

    // Delete
    $ids = db::get_column("SELECT id FROM notifications WHERE subject = 'unit_test_delete'");
    foreach ($ids as $id) { 
        $client->delete($id);
    }

    // Ensure deleted
    $ok = db::get_field("SELECT id FROM notifications WHERE subject = 'unit_test_delete'");
    $this->assertFalse($ok);

}

/**
 * create options
 */
public function test_create_options()
{

    $client = app::make(notification::class);
    $options = $client->create_options();
    $this->assertNotEmpty($options);
    $this->assertStringContains($options, 'optgroup');
    $this->assertStringContains($options, 'System');

}

/**
 * Add mass queue
 */
public function test_add_mass_queue()
{

    $client = app::make(notification::class);
    $queue_id = $client->add_mass_queue('sms', 'system', 'unit test', 'unit test');

    // Check database row
    $row = db::get_idrow('notifications_mass_queue', $queue_id);
    $this->assertNotFalse($row);
    $this->assertArrayHasKey('subject', $row);
    $this->assertEquals('unit test', $row['subject']);

}

}


