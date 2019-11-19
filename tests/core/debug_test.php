<?php
declare(strict_types = 1);

namespace tests\core;

use apex\app;
use apex\svc\db;
use apex\svc\redis;
use apex\svc\log;
use apex\svc\io;
use apex\svc\auth;
use apex\app\sys\debug;
use apex\app\tests\test;


/**
 * Unit tests for the debugger, the PHP class located 
 * at /src/app/sys/debug.php
 */
class test_debug extends test
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
 * Add debug lines
 */
public function test_add()
{

    // Clear log files
    log::terminate();

    // Delete debug.log, if exists
    $debug_file = SITE_PATH . '/storage/logs/debug.log';
    $info_file = SITE_PATH . '/storage/logs/info.log';
    if (file_exists($debug_file)) { @unlink($debug_file); }
    if (file_exists($info_file)) { @unlink($info_file); }

    // Update log levels
    app::update_config_var('core:log_level', 'info,warning,notice,error,critical,alert,emergency');
    log::set_log_levels(explode(",", app::_config('core:log_level')));

    // Update config, as needed
    app::update_config_var('core:debug_level', 3);
    app::update_config_var('core:mode', 'devel');
    $client = new debug();

    // Add level 2
    $client->add(2, tr("unit test 2 {1} {2}", "matt", "was", "here"), 'info');
    $this->assertFileContains($debug_file, 'unit test 2 matt was');
    $this->assertFileContains($info_file, 'unit test 2 matt was');

    // Add debug level 3
    $client->add(3, 'unit test 3');
    $this->assertFileContains($debug_file, 'unit test 3');
    $this->assertFileNotContains($info_file, 'unit test 3');

    // Add level 4
    $client->add(4, 'unit test 4');
    $this->assertFileNotContains($debug_file, 'unit test 4');
    $this->assertFileNotContains($info_file, 'unit test 4');

}

/**
 * Finish debug session
 */
public function test_finish_session()
{

    // Initialize
    $client = new debug();
    $client->add(1, 'unit test');
    redis::del('config:debug_log');
    if (file_exists(SITE_PATH . '/storage/logs/output.html')) { 
        @unlink(SITE_PATH . '/storage/logs/output.html');
    }

    // Set config vars
    app::update_config_var('core:debug', 0);
    app::update_config_var('core:mode', 'prod');

    // Test devel mode at prod
$ok = $client->finish_session();
    $this->assertFalse($ok);

    // Check URI at debugger page.
    app::update_config_var('core:debug', 1);
    app::set_uri('admin/devkit/debugger');
    $ok = $client->finish_session();
    $this->assertFalse($ok);
    app::set_uri('admin/index');
    app::update_config_var('core:debug', 1);
    app::update_config_var('core:mode', 'devel');
    app::set_area('admin');

    // Ensure no debug log saved
    $this->assertFalse(redis::get('config:debug_log'));
    $this->assertFileNotExists(SITE_PATH . '/storage/logs/output.html');

    // Finish session successfully
    $ok = $client->finish_session();
    $this->assertTrue($ok);

    // Check data
    $data = redis::get('config:debug_log');
    $this->assertNotFalse($data);
    $this->assertFileExists(SITE_PATH . '/storage/logs/output.html');
    $this->assertEquals(0, (int) app::_config('core:debug'));

    // Validate $data array
    $this->validate_debug_log($data, 'admin/index', 'admin');

    // Get data, and validate
    $data = json_encode($client->get_data());
    $this->validate_debug_log($data, 'admin/index', 'admin');

}

/**
 * Test diefferent debug modes
 */
public function test_debug_modes()
{

    // Initialize
    $client = new debug();
    $client->add(1, 'unit test');
    app::set_uri('admin/users/create');
    app::set_area('admin');

    // Set config avrs
    app::update_config_var('core:mode', 'prod');
    app::update_config_var('core:debug', 2);

    // Check
    $ok = $client->finish_session();
    $this->assertTrue($ok);
    $this->assertEquals(2, (int) app::_config('core:debug'));

    // Check debug = 1
    app::update_config_var('core:debug', 1);
    $ok = $client->finish_session();
    $this->assertTrue($ok);
    $this->assertEquals(0, (int) app::_config('core:debug'));

    // Make sure we get a false
    $ok = $client->finish_session();
    $this->assertFalse($ok);

    // Check devel mode = on
    app::update_config_var('core:mode', 'devel');
    $ok = $client->finish_session();
    $this->assertTrue($ok);

    // Check data
    $data = redis::get('config:debug_log');
    $this->assertNotFalse($data);
    $this->validate_debug_log($data, 'admin/users/create', 'admin');

}

/**
 * Test the ourput.html file
 */
public function test_output_html_file()
{

    // Initialize
    app::update_config_var('core:mode', 'devel');

    // Delete output.html if exists
    $file = SITE_PATH . '/storage/logs/output.html';
    if (file_exists($file)) { @unlink($file); }

    // Send http request
    $url = 'http://' . app::_config('core:domain_name') . '/login';
    $html = io::send_http_request($url);
    $this->assertNotFalse($html);

    // Check output.html file
    $this->assertFileExists($file);
    $this->assertFileContains($file, 'Login');

}

/**
 * Test debugger tab control
 */
public function test_debugger_tabcontrol()
{

    // Login
    $admin_id = db::get_field("SELECT id FROM admin WHERE username = %s", $_SERVER['apex_admin_username']);
    app::set_area('admin');
    auth::auto_login((int) $admin_id);

    // Send http request
    $url = 'http://' . app::_config('core:domain_name') . '/register';
    $html = io::send_http_request($url);
    $this->assertNotFalse($html);

    // Send http request
    $html = $this->http_request('admin/devkit/debugger');
    $this->assertPageTitle('Debugger');
    $this->assertHasHeading(3, 'Request Details');
    $this->assertHasHeading(3, 'Backtrace');
    $this->assertHasHeading(3, 'Line Items');
    $this->assertHasHeading(3, 'POST');
    $this->assertHasHeading(3, 'GET');
    $this->assertHasHeading(3, 'COOKIE');
    $this->assertHasHeading(3, 'SQL Queries');
    $this->assertPageContains('/register');
    $this->assertPageContains('public');

    // Start debugger again
    $client = new debug();
    $client->add(2, 'unit test');

    // Get tabcontrol with userid
    $html = $this->http_request('admin/settings/general');
    $this->assertPageTitle('General Settings');
    $client->finish_session();

    // Send http request
    $html = $this->http_request('admin/500');
    $this->assertPageTitle('Error');
    $this->assertHasHeading(3, 'Request Details');
    $this->assertHasHeading(3, 'Backtrace');
    $this->assertHasHeading(3, 'Line Items');
    $this->assertHasHeading(3, 'POST');
    $this->assertHasHeading(3, 'GET');
    $this->assertHasHeading(3, 'COOKIE');
    $this->assertHasHeading(3, 'SQL Queries');
    $this->assertPageContains('/register');
    $this->assertPageContains('public');

}

/**
 * Validate debug log
 */
private function validate_debug_log($data, $uri, $area)
{

    // Decode JSON
    $vars = json_decode($data, true);
    $this->assertArrayHasKey('start_time', $vars);
    $this->assertArrayHasKey('end_time', $vars);
    $this->assertArrayHasKey('registry', $vars);
    $this->assertArrayHasKey('uri', $vars['registry']);
    $this->assertArrayHasKey('area', $vars['registry']);
    $this->assertArrayHasKey('post', $vars);
    $this->assertArrayHasKey('get', $vars);
    $this->assertArrayHasKey('cookie', $vars);
    $this->assertArrayHasKey('server', $vars);
    $this->assertArrayHasKey('sql', $vars);
    $this->assertArrayHasKey('notes', $vars);

    // Check values of debug info
    $this->assertEquals($uri, $vars['registry']['uri']);
    $this->assertEquals($area, $vars['registry']['area']);
    $this->assertCount(1, $vars['notes']);

    // Check note
    $note = $vars['notes'][0];
    $this->assertArrayHasKey('level', $note);
    $this->assertArrayHasKey('note', $note);
    $this->assertEquals('debug', $note['level']);
    $this->assertEquals('unit test', $note['note']);

}
}


