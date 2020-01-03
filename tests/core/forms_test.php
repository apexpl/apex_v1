<?php
declare(strict_types = 1);

namespace tests\core;

use apex\app;
use apex\libc\db;
use apex\libc\debug;
use apex\libc\auth;
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
        'phone' => '', 
        'url' => 'invalid_url', 
        'age' => '23a'
    );

    // Send http request
    $html = $this->http_request('index', 'POST', $request);

    // Validate form fields
    $client = new forms();
    $client->validate_fields(
        'template', 
        array('full_name', 'email','username'), 
        array('username' => 'alphanum', 'email' => 'email', 'username2' => 'alphanum', 'amount' => 'decimal', 'amount2' => 'decimal', 'url' => 'url', 'age' => 'integer'), 
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
    $this->assertHasFormError('url', 'url');
    $this->assertHasFormError('integer', 'age');

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

/**
 * Validate fields -- required
 */
public function test_validate_fields_required()
{

    // Set request
    $request = array(
        'first_name' => '', 
        'email' => 'matt@test.com'
    );
    $html = $this->http_request('index', 'POST', $request);

    // Get exception
    $client = new forms();
    $this->waitException('was left blank');
    $client->validate_fields('error', array('first_name'));

}

/**
 * Validate fields - Data Type - Alphanum
 */
public function test_validate_fields_datatype_alphanum()
{

    // Send http request
    $html = $this->http_request('index', 'POST', array('name' => 'GjA@lan%gi ag'));
    $this->waitException('must be alpha-numeric');

    // Validate fields
    $client = new forms();
    $client->validate_fields('error', array(), array('name' => 'alphanum'));

}

/**
 * Validate field - Min length
 */
public function test_validate_fields_minlength()
{

    // Send http request
    $html = $this->http_request('index', 'POST', array('name' => 'ds'));
    $this->waitException('must be a minimum of');

    // Validate fields
    $client = new forms();
    $client->validate_fields('template', array(), array(), array('username' => 4));
    $client->validate_fields('error', array(), array(), array('username' => 4));

}

/**
 * Validate fields - maxlength
 */
public function test_validate_fields_maxlength()
{

    // Send http request
    $html = $this->http_request('index', 'POST', array('name' => 'matt was here'));
    $this->waitException('can not exceed a maximum of');

    // Validate fields
    $client = new forms();
    $client->validate_fields('error', array(), array(), array(), array('name' => 4));

}

/**
 * Validate form - invalid form alias
 */
public function test_validate_form_invalid_form_alias()
{

    $this->waitException('does not exist');
    $client = new forms();
    $client->validate_form('core:some_invalid_junk_form');

}

/**
 * get_date
 */
public function test_get_date()
{

    // Set request
    $request = array(
        'test_year' => '2020', 
        'test_month' => '03', 
        'test_day' => '15'
    );
    $client = new forms();

    // Send http request
    $html = $this->http_request('index', 'POST', $request);
    $this->assertNotEmpty($html);
    $date = $client->get_date('test');
    $this->assertEquals('2020-03-15', $date);

}

}

