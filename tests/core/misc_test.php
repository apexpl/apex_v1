<?php
declare(strict_types = 1);

namespace tests\core;

use apex\app;
use apex\svc\db;
use apex\svc\redis;
use apex\svc\debug;
use apex\svc\components;
use apex\svc\io;
use apex\app\db\db_connections;
use apex\core\admin;
use apex\core\notification;
use apex\app\tests\test;


/**
 * Handles various misc tests that don't warrant a class of their own, 
 * and is used to help obtain 100% code coverage.
 */
class test_misc extends test
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
 * Test invalid timezone.
 */
public function test_app_invalid_timezone()
{

    $vars = app::get_tzdata('junk123');
    $this->assertEquals(0, (int) $vars[0]);
    $this->assertEquals(0, (int) $vars[1]);

}

/**
 * Test app::get_currency()
 */
public function test_app_get_currency()
{

    // Get CAD
    $vars = app::get_currency_data('CAD');
    $this->assertArrayHasKey('decimals', $vars);
    $this->assertArrayHasKey('symbol', $vars);
    $this->assertArrayHasKey('is_crypto', $vars);
    $this->assertEquals(2, (int) $vars['decimals']);
    $this->assertEquals(0, (int) $vars['is_crypto']);

    // Check crypto
    redis::sadd('config:crypto_currency', 'UST');
    $vars = app::get_currency_data('UST');
    $this->assertEquals(8, (int) $vars['decimals']);
    $this->assertEquals(1, (int) $vars['is_crypto']);
    redis::srem('config:crypto_currency', 'UST');

    // Trigger exception
    $this->waitException('Currency does not exist');
    app::get_currency_data('UST');

}

/**
 * app::set_userid()
 */
public function test_app_set_userid()
{

    $userid = app::get_userid();
    app::set_userid(485);

    $this->assertEquals(485, app::get_userid());
    app::set_userid($userid);

}

/**
 * app::set_area()
 */
public function test_app_set_area()
{

    // Change to members
    app::set_area('members');
    $this->assertEquals('members', app::get_area());
    $this->assertEquals(app::_config('users:theme_members'), app::get_theme());

    // Change to public
    app::set_area('public');
    $this->assertEquals('public', app::get_area());
    $this->assertEquals(app::_config('core:theme_public'), app::get_theme());

    // Set invalid area
    $this->waitException('Invalid area specified');
    app::set_area('junk');

}


/**
 * app::getall_request_vars()
 */
public function test_app_getall_request_vars()
{

    // Get vars
    $vars = app::getall_request_vars();
    $this->assertArrayHasKey('host', $vars);
    $this->assertArrayHasKey('port', $vars);
    $this->assertArrayHasKey('method', $vars);
    $this->assertArrayHasKey('content_type', $vars);
    $this->assertArrayHasKey('uri', $vars);
    $this->assertArrayHasKey('ip_address', $vars);
    $this->assertArrayHasKey('userid', $vars);
    $this->assertArrayHasKey('user_agent', $vars);
    $this->assertArrayHasKey('area', $vars);

}

/**
 * App header functions
 */
public function test_app_http_headers()
{


    // Has header
    $this->assertFalse(app::has_header('some_junk_header'));

}

/**
 * Table -- core/table/auth_history -- with 'userid' variable
 */
public function test_core_table_auth_history()
{

    // Load components
    $data = array('userid' => 1, 'type' => 'admin');
    $table = components::load('table', 'auth_history', 'core', '', $data);
    $table->get_attributes($data);

    // Get total
    $total = $table->get_total();
    $this->assertNotFalse($total);

    // Get rows
    $rows = $table->get_rows();
    $this->assertNotFalse($rows);

}

/**
 * Invalid data table alias
 */
public function test_core_htmlfunc_display_table_invalid_alias()
{

    // Set data
    $data = array(
        'html' => '', 
        'data' => ['table' => 'junk_package:and_some_table-that_will_never_exist']
    );
    // Call HTML function
    $response = components::call('process', 'htmlfunc', 'display_table', 'core', '', $data);
    $this->assertNotEmpty($response);
    $this->assertStringContains($response, 'either does not exist');

}

/**
 * Display tab control -- invalid
 */
public function test_core_htmlfunc_display_tabcontrol_invalid()
{

    // Initialize
    $data = array(
        'html' => '', 
        'data' => array()
    );

    // No tabcontrol defined
    $response = components::call('process', 'htmlfunc', 'display_tabcontrol', 'core', '', $data);
    $this->assertNotEmpty($response);
    $this->assertStringContains($response, "attribute was defined");

    // Tab control does not exist
    $data['data']['tabcontrol'] = 'core:some_tabcontrol_that_will_never_exist';
    $response = components::call('process', 'htmlfunc', 'display_tabcontrol', 'core', '', $data);
    $this->assertNotEmpty($response);
    $this->assertStringContains($response, 'The tab control');
    $this->assertStringContains($response, 'either does not exist');

    // Load HTML function
    $htmlfunc = components::load('htmlfunc', 'display_tabcontrol', 'core');
    $this->assertNotFalse($htmlfunc);

    // Get debugger tab control
    $tab = components::load('tabcontrol', 'debugger', 'core');
    $tabpages = $this->invoke_method($htmlfunc, 'get_tab_pages', array($tab->tabpages, 'debugger', 'core'));
    $this->assertIsArray($tabpages);

}

/**
 * Tab control -- core -- dashboard
 */
public function test_core_tabcontrol_dashboard()
{

    // Initialize
    $data = array(
        'html' => '',  
        'data' => array('tabcontrol' => 'dashboard', 'profile_id' => 1)
    );

    // Load tabcontrol
    $tab = components::load('tabcontrol', 'dashboard', 'core', '', ['profile_id' => 1]);
    $this->assertNotFalse($tab);

    // Load HTML function
    $htmlfunc = components::load('htmlfunc', 'display_tabcontrol', 'core', '', $data);
    $this->assertNotFalse($htmlfunc);

    // Get tab pages
    $tabpages = $this->invoke_method($htmlfunc, 'get_tab_pages', array($tab->tabpages, 'dashboard', 'core'));
    $this->assertIsArray($tabpages);

}

/**
 * Display form - invalid
 */
public function test_core_htmlfunc_display_form_invalid()
{

    // Initialize
    $data = array(
        'html' => '', 
        'data' => ['form' => 'some_form_that_will_never_exist']
    );

    // Form with invalid alias
    $response = components::call('process', 'htmlfunc', 'display_form', 'core', '', $data);
    $this->assertNotEmpty($response);
    $this->assertStringContains($response, 'The form with the alias');
    $this->assertStringContains($response, 'either does not exist');

}

/**
 * Core - Form - Dashboard Profile Items - Invalid profile ID#
 */
public function test_core_form_dashboard_profiles_items_invalid()
{

    // Set data
    $vars = array(
        'data' => ['profile_id' => 83532752]
    );

    // Wait exception
    $this->waitException('Dashboard does not exist');
    components::call('get_fields', 'form', 'dashboard_profiles_items', 'core', '', $vars);

}

/**
 * Test admin panel disabled.
 */
public function test_admin_panel_disabled()
{

    // Send http request
    $html = $this->http_request('admin/login', 'GETT', array(), array('disable_admin' => 1));
    $this->assertPageTitle('Page Not Found');

}

/**
 * Core - Table - delete_rows - Invalid table
 */
public function test_core_ajax_delete_rows_invalid()
{

    // Initialize
    $vars = array(
        'table' => 'core:some_table-that_willl_never_exists'
    );

    // Send http request
    $url = 'http://' . app::_config('core:domain_name') . '/ajax/core/delete_rows';
    $response = io::send_http_request($url, 'POST', $vars);
    $this->assertNotEmpty($response);
    $this->assertStringContains($response, 'does not exist');

}

/**
 * Load admin - not exists
 */
public function test_admin_load_not_exists()
{

    $this->waitException('No administrator exists');
    $admin = app::make(admin::class, ['id' => 938526]);
    $admin->load();

}

/**
 * Admin - update_status
 */
public function test_admin_update_status()
{

    // Initialize
    $admin_id = db::get_field("SELECT id FROM admin WHERE username = %s", $_SERVER['apex_admin_username']);
    $admin = app::make(admin::class, ['id' => (int) $admin_id]);

    // Update
    $admin->update_status('inactive');
    $chk = db::get_field("SELECT status FROM admin WHERE id = %i", $admin_id);
    $this->assertEquals('inactive', $chk);

    // Activate again
    $admin->update_status('active');
    $chk = db::get_field("SELECT status FROM admin WHERE id = %i", $admin_id);
    $this->assertEquals('active', $chk);

}

/**
 * Update invalid config variable
 */
public function test_update_config_var_invalid()
{

    $this->waitException('Unable to update configuration variable');
    app::update_config_var('some_invalid_junk_var', 'test');

}

/**
 * app - _header
 */
public function test_app_header()
{

    $var = app::_header_line('content-type');
    $var = app::_header('content-type');
    $this->assertIsArray($var);

    $headers = app::get_res_all_headers();
    $this->assertIsArray($headers);


}

/**
 * app - echo_response
 */
public function notest_app_echo_response()
{

    // Initialize
    app::set_area('public');
    app::set_uri('login');
    app::set_userid(0);

    // Send http requet
    $html = $this->http_request('register');

    // Echo response
    ob_start();
    app::echo_response();
    $html = ob_get_contents();
    ob_end_clean();

    // Assert
    $this->assertNotEmpty($html);
    $this->assertStringContains($html, 'Login Now');

}




}


