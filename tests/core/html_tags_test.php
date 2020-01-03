<?php
declare(strict_types = 1);

namespace tests\core;

use apex\app;
use apex\libc\db;
use apex\libc\debug;
use apex\libc\components;
use apex\app\web\html_tags as tags;
use apex\app\utils\tables;
use apex\app\tests\test;


/**
 * Handles the unit tests for the tags library located 
 * at /src/app/web/html_tags.php
 */
class test_html_tags extends test
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
 * ft_row -- no name attribute
 */
public function test_ft_row_no_name()
{

    $tags = app::make(tags::class);
    $vars = array(
        'form_field' => 'textbox', 
        'attr' => [], 
        'text' => ''
    );
    $response = $this->invoke_method($tags, 'ft_row', $vars);
    $this->assertStringContains($response, "No 'name' attribute");

}

/**
 * ft_blank
 */
public function test_ft_blank()
{

    $tags = app::make(tags::class);
    $html = $tags->ft_blank([], 'unit test');
    $this->assertEquals('<tr><td colspan="2">unit test</td></tr>', $html);

}

/**
 * textbox
 */
public function test_textbox()
{

    $tags = app::make(tags::class);
    $vars = array(
        'name' => 'unit_test', 
        'value' => 'matt', 
        'onkeyup' => 'unit_test', 
        'placeholder' => 'my name'
    );

    // Get HTML
    $html = $tags->textbox($vars);
    $this->assertStringContains($html, 'unit_test');
    $this->assertStringContains($html, 'onkeyup');

}

/**
 * amount -- no name
 */
public function test_amount_no_name()
{

    $tags = app::make(tags::class);
    $html = $tags->amount([]);
    $this->assertStringContains($html, "There is no 'name' attribute");

    // Get amount
    $html = $tags->amount(['name' => 'unit_test']);
    $this->assertStringContains($html, 'text');
    $this->assertStringContains($html, 'unit_test');

}

/**
 * phone
 */
public function test_phone()
{

    $tags = app::make(tags::class);
    $html = $tags->phone([]);
    $this->assertStringContains($html, "The 'phone' tag does not have");

    // Valid phone
    $vars = array(
        'name' => 'phone', 
        'value' => '+1 6045551234'
    );
    $html = $tags->phone($vars);
    $this->assertStringContains($html, 'phone');
    $this->assertStringContains($html, '6045551234');

}

/**
 * textarea
 */
public function test_textarea()
{

    $tags = app::make(tags::class);

    // Validate
    $vars = array(
        'name' => 'unit_test', 
        'placeholder' => 'test placeholder' 
    );
    $html = $tags->textarea($vars);
    $this->assertStringContains($html,'unit_test');
    $this->assertStringContains($html, 'test placeholder');

}

/**
 * input_box
 */
public function test_input_box()
{

    $tags = app::make(tags::class);
    $html = $tags->input_box([], 'unit_test');
    $this->assertStringContains($html, 'unit_test');

}

/**
 * Pagination
 */
public function test_pagination()
{

    // Load table
    $tbl_client = app::make(tables::class);
    $table = components::load('table', 'admin', 'core');
    $details = $tbl_client->get_details($table, 'tbl_core_admin');

    // Update details
    $details['total'] = 108;
    $details['total_pages'] = 5;
    $details['end_page'] = 5;
    $details['id'] = 'tbl_core_admin';
    $details['ajaxdata'] = "table=core:admin&id=tbl_core_admin";

    // Get pagination
    $tags = app::make(tags::class);
    $html = $tags->pagination($details);
    $this->assertNotEmpty($html);

    // Get pagination, with href defined
    $details['href'] = 'route';
    $html = $tags->pagination($details);
    $this->assertNotEmpty($html);

}

/**
 * boxlists
 */
public function test_boxlists()
{


    $tags = app::make(tags::class);
    $html = $tags->boxlist(['alias' => 'users:settings']);
    $this->assertNotEmpty($html);
    $this->assertStringContains($html, 'General');

}

/**
 * date
 */
public function test_date()
{

    // No_name attribute
    $tags = app::make(tags::class);
    $html = $tags->date([]);
    $this->assertStringContains($html, "No 'name' attribute");

    // Valid date
    $html = $tags->date(['name' => 'unit_test']);
    $this->assertStringContains($html, 'unit_test');
    $this->assertStringContains($html, 'March');
    $this->assertStringContains($html, '2021');

    // Date with value
    $html = $tags->date(['name' => 'unit_test', 'value' => '2020-03-14']);
    $this->assertStringContains($html, 'unit_test');
    $this->assertStringContains($html, 'March');
    $this->assertStringContains($html, '2021');

}

/**
 * time
 */
public function test_time()
{

    // no name attribute
    $tags = app::make(tags::class);
    $html = $tags->time([]);
    $this->assertStringContains($html, "No 'name' attribute");

    // Validate time
    $html = $tags->time(['name' => 'unit_test']);
    $this->assertStringContains($html, 'unit_test');

}

/**
 * date_interval
 */
public function test_date_interval()
{

    // Valid with no value
    $tags = app::make(tags::class);
    $html = $tags->date_interval(['name' => 'unit_test']);
    $this->assertNotEmpty($html);
    $this->assertStringContains($html, 'unit_test');

}

/**
 * placeholder
 */
public function test_placeholder()
{

    // No alias
    $tags = app::make(tags::class);
    $html = $tags->placeholder([]);
    $this->assertEmpty($html);

    // Validate placeholder
    app::set_uri('public/login');
    $html = $tags->placeholder(['alias' => 'after_form']);
    $this->assertTrue(true);

}

/**
 * recaptch
 */
public function test_recaptcha()
{

    // Initailize
    $orig_key = app::_config('core:recaptcha_site_key');
    $tags = app::make(tags::class);

    // Blank HTML
    app::update_config_var('core:recaptcha_site_key', '');
    $html = $tags->recaptcha([]);
    $this->assertEmpty($html);

    // Valid recaptcha
    app::update_config_var('core:recaptcha_site_key', 'unit_test');
    $html = $tags->recaptcha([]);
    $this->assertNotEmpty($html);
    $this->assertStringContains($html, 'recaptcha');

    // Reset
    app::update_config_var('core:recaptcha_site_key', $orig_key);

}

}


