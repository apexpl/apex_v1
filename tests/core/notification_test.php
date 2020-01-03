<?php
declare(strict_types = 1);

namespace tests\core;

use apex\app;
use apex\libc\db;
use apex\libc\debug;
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
 * Notification - merge_fields - invalid adapter
 */
public function test_notification_get_merge_fields_invalid_adapter()
{

    // Get exception
    $this->waitException('does not exist');
    $client = app::make(notification::class);
    $client->get_merge_fields('some_invalid_junk_adapter');

}

/**
 * Notification - merge_vars - invalid adapter
 */
public function test_notification_get_merge_vars_invalid_adapter()
{

    // Get exception
    $this->waitException('does not exist');
    $client = app::make(notification::class);
    $client->get_merge_vars('some_invalid_junk_adapter');

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
 * Create - no adapter defined
 */
public function test_create_adapter_undefined()
{

    $this->waitException('No notification adapter was defined');
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
    $client->create(array('adapter' => 'system'));

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
        'adapter' => 'system', 
        'sender' => 'admin:1'
    );
    $client->create($vars);

}

/**
 * create - invalid adapter
 */
public function test_create_invalid_adapter()
{

    $this->waitException('does not exist');
    $client = app::make(notification::class);

    // Set vars
    $vars = array(
        'adapter' => 'some_junk_invalid_adapter', 
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
        'adapter' => 'system', 
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


