<?php
declare(strict_types = 1);

namespace tests\core;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\svc\auth;
use apex\app\utils\forms;
use apex\app\tests\test;


/**
 * Contains various unit tests to handle HTML forms with the 
 * PHP class at /src/app/utils/forms.php
 */
class test_forms extends test
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

    // Ensure logged out
    auth::logout();
    app::set_area('public');
    app::set_userid(0);
    app::clear_cookie();


}

/**
 * Test validate_fields()
 */
public function test_validate_fields()
{

    // Set request
    $request = array(
        'username' => 'johnsmi@thwashere',
        'username2' => 'jasond',  
        'full_name' => '', 
        'email' => 'johnsmith.com', 
        'amount' => 'abc', 
        'amount2' => 15.33, 
        'phone' => ''
    );

    // Send http request
    $html = $this->http_request('index', 'POST', $request);

    // Validate form fields
    $client = new forms();
    $client->validate_fields(
        'template', 
        array('full_name', 'email','username'), 
        array('username' => 'alphanum', 'email' => 'email', 'username2' => 'alphanum', 'amount' => 'decimal', 'amount2' => 'decimal'), 
        array('username' => 3), 
        array('username' => 8)
    );

    // Blank assertions
    $this->assertNotHasFormError('blank', 'username');
    $this->assertHasFormError('blank', 'full_name');
    $this->assertNotHasFormError('blank', 'email');
    $this->assertNotHasFormError('blank', 'phone');

    // Data type assertions
    $this->assertHasFormError('email', 'email');
    $this->assertHasFormError('alphanum', 'username');
    $this->assertNotHasFormError('alphanum', 'username2');
    $this->assertHasFormError('decimal', 'amount');
    $this->assertNotHasFormError('decimal', 'amount2');

}

/**
 * Test validate_form()
 */
public function test_validate_form()
{

    // Set request
    $request = array(
        'username' => 'unittest', 
        'password' => 'mytest', 
        'confirm-password' => 'mytest', 
        'full_name' => '', 
        'email' => 'John Smith', 
        'phone' => '', 
        'phone_country' => '', 
        'require_2fa' => 0, 
        'require_2fa_phone' => 0, 
        'lanauge' => 'en', 
        'timezone' => 'PST', 
        'question1' => 'q1', 
        'question2' => 'q2', 
        'question3' => 'q3', 
        'answer1' => 'a', 
        'answer2' => 'a', 
        'answer3' => 'a'
    );

    // Send http request
    $this->http_request('index', 'POST', $request);

    // Validate form
    $client = new forms();
    $client->validate_form('core:admin');

    // Assertions
    $this->assertNotHasFormError('blank', 'username');
    $this->assertNotHasFormError('alphanum', 'username');
    $this->assertNotHasFormError('blank', 'password');
    $this->assertHasFormError('blank', 'full_name');
    $this->assertHasFormError('email', 'email');

}

/**
 * Test get_chk()
 */
public function test_get_chk()
{

    // Set request
    $request = array(
        'one' => 'hello', 
        'amount' => array(35.22), 
        'cards' => array(3, 7, 11, 52), 
    );

    // Send http request
    $this->http_request('index', 'POST', $request);

// Check one
    $client = new forms();
    $chk = $client->get_chk('one');
    $this->assertIsIterable($chk);
    $this->assertCount(1, $chk);
    $this->assertContains('hello', $chk);

    // Assert amount
    $chk = $client->get_chk('amount');
    $this->assertIsIterable($chk);
    $this->assertCount(1, $chk);
    $this->assertContains(35.22, $chk);

    // Assert cards
    $chk = $client->get_chk('cards');
    $this->assertIsIterable($chk);
    $this->assertCount(4, $chk);
    $this->assertContains(3, $chk);
    $this->assertContains(7, $chk);
    $this->assertContains(11, $chk);
    $this->assertContains(52, $chk);

    // Assert junk
    $chk = $client->get_chk('junk');
    $this->assertIsIterable($chk);
    $this->assertCount(0, $chk);

}

/**
 * Test get_date_interval()
 */
public function test_get_date_interval()
{

    // Set request
    $request = array(
        'one_period' => 'D', 
        'one_num' => '', 
        'two_period' => '', 
        'two_num' => 5, 
        'three_period' => 'D', 
        'three_num' => 'abc', 
        'four_period' => 'M', 
        'four_num' => 3
    );

    // Send http request
    $this->http_request('index', 'POST', $request);

    // Check
    $client = new forms();
    $this->assertEmpty($client->get_date_interval('one'));
    $this->assertEmpty($client->get_date_interval('two'));
    $this->assertEmpty($client->get_date_interval('three'));
    $this->assertEquals('M3', $client->get_date_interval('four'));

}

}

