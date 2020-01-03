<?php
declare(strict_types = 1);

namespace tests\core;

use apex\app;
use apex\libc\db;
use apex\libc\redis;
use apex\libc\debug;
use apex\libc\auth;
use apex\app\msg\emailer;
use apex\app\tests\test;

/** 
 * Handles all unit tests for the authentication library 
 * at /src/app/sys/auth.php
 */
class test_auth extends test
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
 * 2FA - admin - e-mail
 */
public function test_2fa_admin_email()
{

    // Clear e-mail queue
    $emailer = app::get(emailer::class);
    $emailer->clear_queue();
    app::set_area('admin');

    // Logout
    $html = $this->http_request('admin/logout');
    $this->assertPageTitle('Logged Out');

    // Update admin
    db::query("UPDATE admin SET require_2fa = 1, require_2fa_phone = 0");

    // Login
    $request = array(
        'username' => $_SERVER['apex_admin_username'], 
        'password' => $_SERVER['apex_admin_password'], 
        'submit' => 'login'
    );
    $html = $this->http_request('admin/index', 'POST', $request);
    $this->assertPageTitle('2FA Authentication Required');

    // Check e-mail eueue   // Check e-mail queue
    $messages = $emailer->search_queue('', '', '2FA Required');
    $this->assertCount(1, $messages);
    $message = $messages[0]->get_message();

    // Get 2FA link
    if (!preg_match("/\/auth2fa\/(.+)/", $message, $match)) { 
        $this->assertTrue(false, "Unable to find 2FA link within e-mail");
    }
    $this->assertTrue(true);

    // Invalid request
    $html = $this->http_request('auth2fa/somejunkrequest');
    $this->assertPageTitle('Invalid 2FA Request');

    // Login
    $html = $this->http_request('auth2fa/' . $match[1]);
    $this->assertPageTitleContains('Welcome');

    // Ensure we're still logged in
    $html = $this->http_request('admin/settings/general');
    $this->assertPageTitle('General Settings');

    // Clean up
    db::query("UPDATE admin SET require_2fa = 0, require_2fa_phone = 0");


}






}

