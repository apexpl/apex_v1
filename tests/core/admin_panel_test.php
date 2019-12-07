<?php
declare(strict_types = 1);

namespace tests\core;

use apex\app;
use apex\svc\db;
use apex\svc\redis;
use apex\svc\forms;
use apex\svc\components;
use apex\svc\encrypt;
use apex\svc\auth;
use apex\svc\io;
use apex\app\sys\network;
use apex\app\db\db_connections;
use apex\core\admin;
use apex\app\tests\test;


/**
 * Contains unit tests for various functionality within the administration panel, 
 * mainly the general settings, maintenance, and administrator management features.
 */
class test_admin_panel extends test
{

/**
 * setUp 
 */
public                      function setUp():void
{ 

    if (!$app = app::get_instance()) { 
        $app = new app('test');
    }

    // Get db / email slave servers
    $this->dbslaves = redis::lrange('config:db_slaves', 0, -1);
    $this->email_servers = redis::lrange('config:email_servers', 0, -1);
    $this->rabbitmq = redis::hgetall('config:rabbitmq');

}

/**
 * tearDown
 */
public function tearDown():void
{ 


    // Reset db slave servers
    redis::del('config:db_slaves');
    foreach ($this->dbslaves as $row) { 
        redis::rpush('config:db_slaves', $row);
    }

    // Reset e-mail servers
    redis::del('config:email_servers');
    foreach ($this->email_servers as $data) { 
        redis::rpush('config:email_servers', $data);
    }

    // RabbitMQ
    redis::hmset('config:rabbitmq', $this->rabbitmq);

}

/**
 * Login form 
 */
public function test_login()
{ 

    // Ensure devkit package is installed
    if (!check_package('devkit')) { 
        echo "Unable to run unit tests, as they require the 'devkit' package to be installed.  You may install it by typing: php apex.php install devkit\n";
        exit;
    } elseif (!$admin_id = db::get_field("SELECT id FROM admin WHERE username = %s", $_SERVER['apex_admin_username'])) { 
        echo "Unable to execute unit tests, as no administrator with the username $_SERVER[apex_admin_username] exists.  You must first create an administrator, and ensure the login details match those within the environments variables within the /phpunit.xml file.\n\n";
        exit;
    }

    // Get login form
    $html = $this->http_request('/admin');
    $this->assertPageTitle('Login Now');

    // Login
    $vars = array(
        'username' => $_SERVER['apex_admin_username'], 
        'password' => $_SERVER['apex_admin_password'], 
        'submit' => 'login'
    );

    $html = $this->http_request('/admin/login', 'POST', $vars);

    $this->assertPageTitleContains("Welcome");

}

/**
 * Settings->General Settings page 
 */
public function test_page_admin_settings_general()
{ 

    // Check if page loads
    $html = $this->http_request('/admin/settings/general');
    $this->assertPageTitle('General Settings');
    $this->assertHasHeading(3, 'General');
    $this->assertHasHeading(3, 'Site Info');
    $this->assertHasHeading(3, 'API Keys');
    $this->assertHasHeading(3, 'Admin Panel Security');
    $this->assertHasHeading(3, 'Database Servers');
    $this->assertHasHeading(3, 'E-Mail Servers');
    $this->assertHasHeading(3, 'Storage Settings');
    $this->assertHasHeading(3, 'Reset Redis');

    // Assert submit buttons
    $this->assertHasSubmit('update_general', 'Update General Settings');
    $this->assertHasSubmit('site_info', 'Update Site Info');
    $this->assertHasSubmit('api_keys', 'Update API Keys');
    $this->assertHasSubmit('security', 'Update Security Settings');
    $this->assertHasSubmit('delete_database', 'Delete Checked Databases');
    $this->assertHasSubmit('delete_email', 'Delete Checked E-Mail Servers');
    $this->assertHasSubmit('storage', 'Update Storage Settings');
    $this->assertHasSubmit('reset_redis', 'Reset Redis Database');
    $this->assertHasSubmit('add_database', 'Add Database');
    $this->assertHasSubmit('add_email', 'Add SMTP Server');


    // Assert form fields
    $this->assertHasFormField(array('site_name','site_address','site_address2','site_email','site_phone','site_email'));
    $this->assertHasFormField(array('site_facebook','site_twitter','site_linkedin','site_youtube','site_reddit','site_instagram'));
    $this->assertHasFormField(array('dbname','dbuser','dbpass','dbhost','dbport'));
    $this->assertHasFormField(array('email_host','email_user','email_pass','email_port'));
    $this->assertHasFormField(array('storage_sftp_host','storage_sftp_username','storage_sftp_password','storage_sftp_port'));

    // Assert additional
    $this->assertHasTable('core:db_servers');
    $this->assertHasTable('core:email_servers');
    $this->assertHasHeading(4, 'Add Database Server');
    $this->assertHasHeading(4, 'Add SMTP E-Mail Server');

    // Get current config vars
    $orig_config = app::getall_config();

    // Set general settings vars
    $general_vars = array(
        'domain_name' => 'unit-test.com',
        'date_format' => 'Y-m-d H:i:s',
        'default_language' => 'es',
        'default_timezone' => 'MST',
        'log_level' => 'utest,info,degub',
        'debug_level' => 3,
        'mode' => 'prod',
        'cache' => 0, 
        'server_type' => 'all', 
        'enable_javascript' => 1, 
        'websocket_port' => '8194', 
        'submit' => 'update_general'
    );

    // Update general settings
    $html = $this->http_request('/admin/settings/general', 'POST', $general_vars);
    $this->assertPageContains('Successfully updated general settings');
    $this->assertHasCallout('success', "updated general settings");

    // Check config vars
    foreach ($general_vars as $key => $value) { 
        if ($key == 'submit') { continue; }
 
        $chk = redis::hget('config', 'core:' . $key);
        $this->assertequals($chk, $value, tr("General Settings error.  Unable to update convig variable '%s' to '%s'", $key, $value));
        app::update_config_var('core:' . $key, (string) $orig_config['core:' . $key]);
    }


    // Set site info vars
    $siteinfo_vars = array(
        'site_name' => 'Apex Unit Test',
        'site_address' => '555 Burrard Street',
        'site_address2' => 'Chicago, IL 930256',
        'site_email' => 'support@unit-test.com',
        'site_phone' => '582-666-3251',
        'site_tagline' => 'Unit tests are here to stay',
        'site_about_us' => 'unit_about', 
        'site_facebook' => 'unit_fb',
        'site_twitter' => 'unit_twit',
        'site_linkedin' => 'unit_linked',
        'site_youtube' => 'unit_youtube', 
        'site_reddit' => 'unit_reddit', 
        'site_instagram' =>'unit_instagram',
        'submit' => 'site_info'
    );

    // Update site info vars
    $html = $this->http_request('/admin/settings/general', 'POST', $siteinfo_vars);
    $this->assertPageContains('Successfully updated site info settings');
    $this->assertHasCallout('success', "updated site info settings");

    // Check config vars
    foreach ($siteinfo_vars as $key => $value) { 
        if ($key == 'submit') { continue; }
        $chk = redis::hget('config', 'core:' . $key);
        $this->assertequals($chk, $value, tr("General Settings error.  Unable to update convig variable '%s' to '%s'", $key, $value));
        app::update_config_var('core:' . $key, (string) $orig_config['core:' . $key]);
    }

    // Set API keys vars
    $api_keys_vars = array(
        'nexmo_api_key' => 'utest_nexmo_api_key',
        'nexmo_api_secret' => 'utest_nexmo_secret',
        'recaptcha_site_key' => 'utest_recaptcha_set_key',
        'recaptcha_secret_key' => 'utest_recaptcha_secret',
        'openexchange_app_id' => 'test_openexchange_api_key', 
        'submit' => 'api_keys'
    );

    // Send http request to update API keys
    $html = $this->http_request('admin/settings/general', 'POST', $api_keys_vars);
    $this->assertPageTitle('General Settings');
    $this->assertHasCallout('success', 'Successfully updated API');

    // Check config
    foreach ($api_keys_vars as $key => $value) { 
        if ($key == 'submit') { continue; }
        $this->assertEquals($value, app::_config('core:' . $key));
        app::update_config_var('core:' . $key, (string) $orig_config['core:' . $key]);
    }

    // Set security vars
    $security_vars = array(
        'session_expire_mins' => 45,
        'password_retries_allowed' => 8,
        'require_2fa' => 2,
        'session_retain_logs_period' => 'W',
        'session_retain_logs_num' => '1',
        'force_password_reset_time_period' => 'D',
        'force_password_reset_time_num' => '90',
        'submit' => 'security'
    );

    // Update security vars
    $html = $this->http_request('/admin/settings/general', 'POST', $security_vars);
    $this->assertPageContains('Successfully updated admin panel security settings');
    $this->assertHasCallout('success', "updated admin panel security");

    // Modify vars as needed
    unset($security_vars['session_retain_logs_period']);
    unset($security_vars['session_retain_logs_num']);
    unset($security_vars['force_password_reset_time_period']);
    unset($security_vars['force_password_reset_time_num']);
    $security_vars['session_retain_logs'] = 'W1';
    $security_vars['force_password_reset_time'] = 'D90';

    // Check config vars
    foreach ($security_vars as $key => $value) { 
        if ($key == 'submit') { continue; }
        $chk = redis::hget('config', 'core:' . $key);
        $this->assertequals($chk, $value, tr("General Settings error.  Unable to update convig variable '%s' to '%s'", $key, $value));
        app::update_config_var('core:' . $key, (string) $orig_config['core:' . $key]);
    }

    // Get existingt database info
    $db = redis::hgetall('config:db_master');
    redis::del('config:db_slaves');

    // Set add db server vars
    $vars = array(
        'dbname' => $db['dbname'],
        'dbuser' => $db['dbuser'],
        'dbpass' => $db['dbpass'],
        'dbhost' => $db['dbhost'],
        'dbport' => $db['dbport'],
        'submit' => 'add_database'
    );

    // Add database servers
    for ($x = 1; $x <= 3; $x++) { 
        $vars['dbuser'] = 'slave' . $x;
        $html = $this->http_request('/admin/settings/general', 'POST', $vars);
        $this->assertPageContains('Successfully added new database server');
        $this->assertHasCallout('success', 'added new database server');
    }

    // Check count of slave servers
    $count = redis::llen('config:db_slaves');
    $this->assertequals(3, $count, "Did not create 3 slave db servers");

    // Initialize
    $connections = new db_connections();

    // Get rotation of db servers
    redis::hset('counters', 'db_server', 2);
    for ($x=1; $x <= 6; $x++) { 
        $chk_x = $x > 3 ? ($x - 3) : $x;
        $vars = $this->invoke_method($connections, 'get_server_info', array('type' => 'read'));
        $this->assertequals('slave' . $chk_x, $vars['dbuser'], "Rotating slave servers did not work on loop $x");
    }

    // Get write connection
    $vars = $this->invoke_method($connections, 'get_server_info', array('type' => 'write'));
    $this->assertequals($vars['dbuser'], $db['dbuser'], "Unable to retrieve master db info");

    // Get update db server page
    $html = $this->http_request('/admin/settings/general_db_manage', 'GET', array(), array('server_id' => 1));
    $this->assertPageTitle('Manage Database Server');

    // Set vars to update database server
    $vars = json_decode(redis::lindex('config:db_slaves', 1), true);
    $vars['dbuser'] = 'unit_test';
    $vars['submit'] = 'update_database';
    $vars['server_id'] = 1;

    // Update database
    $html = $this->http_request('/admin/settings/general', 'POST', $vars);
    $this->assertPageContains('Successfully updated database server');
    $this->assertHasCallout('success', 'Successfully updated database server');

    // Verify database server was updated
    $vars = json_decode(redis::lindex('config:db_slaves', 1), true);
    $this->assertequals($vars['dbuser'], 'unit_test', "Unable to update slave db server");

    // Delete DB serversw
    $vars = array(
        'db_server_id' => array(0, 2),
        'submit' => 'delete_database'
    );
    $html = $this->http_request('/admin/settings/general', 'POST', $vars);
    $this->assertPageContains('Successfully deleted checked database servers');
    $this->assertHasCallout('success', 'Successfully deleted checked database servers');

    // Check remaining slave server
    $vars = json_decode(redis::lindex('config:db_slaves', 0), true);
    $this->assertequals($vars['dbuser'], 'unit_test', "Deleting slave servers didn't work");

    // Delete slaves servers, update read-only info
    redis::del('config:db_slaves');
    redis::hmset('config:db_master', array('dbuser_readonly' => 'read_test', 'dbpass_readonly' => 'read_pass'));

    // Test read-only user
    $vars = $this->invoke_method($connections, 'get_server_info', array('type' => 'read'));
    $this->assertEquals($vars['dbuser'], 'read_test');
    $this->assertEquals($vars['dbpass'], 'read_pass');

    // Check write connection
    $vars = $this->invoke_method($connections, 'get_server_info', array('type' => 'write'));
    $this->assertNotEquals($vars['dbuser'], 'read_test');
    $this->assertNotEquals($vars['dbpass'], 'read_pass');
    redis::hmset('config:db_master', array('dbuser_readonly' => '', 'dbpass_readonly' => ''));


    // Set SMTP server vars
    $vars = array(
        'email_is_ssl' => 1,
        'email_host' => 'mail.envrin.com',
        'email_user' => 'email1',
        'email_pass' => 'mypassword',
        'email_port' => 25,
        'submit' => 'add_email'
    );
    redis::del('config:email_servers');
    for ($x=1; $x <= 3; $x++) { 
        $vars['email_user'] = 'email' . $x;
        $html = $this->http_request('/admin/settings/general', 'POST', $vars);
        $this->assertPageContains('Successfully added new SMTP e-mail server');
        $this->assertHasCallout('success', 'Successfully added new SMTP e-mail server');
    }

    // Check number of e-mail servers
    $count = redis::llen('config:email_servers');
    $this->assertequals($count, 3, "Unable to create 3 e-mail servers");

    // Delete e-mail servers
    $vars = array(
        'email_server_id' => array(0, 2),
        'submit' => 'delete_email'
    );
    $html = $this->http_request('/admin/settings/general', 'POST', $vars);
    $this->assertPageContains('Successfully deleted all checked e-mail SMTP servers');
    $this->assertHasCallout('success', 'Successfully deleted all checked e-mail SMTP servers');

    // Ensure e-mail servers deleted correctly
    $vars = json_decode(redis::lindex('config:email_servers', 0), true);
    $this->assertequals($vars['username'], 'email2', "Unable to correctly delete SMTP e-mail servers");

    // Set storage vars
    $request = array(
        'storage_type' => 'dropbox', 
        'storage_dropbox_auth_token' => 'unit_test', 
        'submit' => 'storage'
    );
    $html = $this->http_request('admin/settings/general', 'POST', $request);
    $this->assertPageTitle('General Settings');
    $this->assertHasCallout('success', 'Successfully updated remote storage');

    // Check storage config
    //$chk_vars = json_decode(app::_config('core:flysystem_credentials'), true);
    $this->assertEquals('dropbox', app::_config('core:flysystem_type'));
    //$this->assertArrayHasKey('dropbox_auth_token', $chk_vars);
    //$this->assertEquals('unit_test', $chk_vars['dropbox_auth_token']);
    app::update_config_var('core:flysystem_type', 'local');



    // Reset redis -- validation error
    $vars = array(
        'redis_reset' => 'no',
        'submit' => 'reset_redis'
    );
    $html = $this->http_request('/admin/settings/general', 'POST', $vars);
    $this->assertPageContains('You did not enter RESET in the provided text box');
    $this->assertHasCallout('error', 'You did not enter RESET in the provided text box');

    // Reset redis
    $vars['redis_reset'] = 'reset';
    $html = $this->http_request('/admin/settings/general', 'POST', $vars);
    $this->assertPageContains('Successfully reset the redis database');
    $this->assertHasCallout('success', 'Successfully reset the redis database');

}

/**
 * Create first administrator
 */
public function test_create_first_admin()
{

    // Logout
    auth::logout();
    app::set_userid(0);
    app::clear_cookie();

    // Get admin
    $row = db::get_row("SELECT * FROM admin WHERE username = %s", $_SERVER['apex_admin_username']);
    $this->assertNotFalse($row);
    db::query("TRUNCATE admin");

    // Send http request
    $html = $this->http_request('admin/index');
    $this->assertPageTitle('Create First Administrator');
    $this->assertHasFormField(array('username', 'password', 'full_name', 'email', 'phone'));
    $this->assertHasSubmit('create', 'Create New Administrator');

    // Set request
    $request = array(
        'username' => 'a junk @kds! user', 
        'password' => $_SERVER['apex_admin_password'], 
        'confirm-password' => $_SERVER['apex_admin_password'], 
        'full_name' => $row['full_name'], 
        'email' => $row['email'], 
        'phone_country' => $row['phone_country'], 
        'phone' => $row['phone'], 
        'require_2fa' => $row['require_2fa'], 
        'require_2fa_phone' => $row['require_2fa_phone'], 
        'language' => $row['language'], 
        'timezone' => $row['timezone'], 
        'submit' => 'create'
    );

    // Get error with http request
    $html = $this->http_request('admin/index', 'POST', $request);
    $this->assertPageTitle('Create First Administrator');
    $this->assertHasCallout('error', 'must be alpha-numeric');

    // Send http request
    $request['username'] = $_SERVER['apex_admin_username'];
    $html = $this->http_request('admin/index', 'POST', $request);
    $this->assertPageTitleContains('Welcome');

    // Check database row
    $row = db::get_row("SELECT * FROM admin WHERE username = %s", $_SERVER['apex_admin_username']);
    $this->assertNotFalse($row);

}

/**
* Create administrators 
* @dataProvider provider_create_admin 
 */
public function test_create_admin(array $vars, string $error_type = '', string $field_name = '')
{ 

    // Delete admin, if exists
    if ($error_type == 'blank' && $field_name == 'username') { 
        db::query("DELETE FROM admin WHERE username = 'unit_test'");
    }

    // Send request
    $html = $this->http_request('/admin/settings/admin', 'POST', $vars);

    // Check response
    if ($error_type == 'dupe') { 
        $this->assertHasCallout('error', 'The username already exists'); 
    } elseif ($error_type != '') { 
        $this->assertHasFormError($error_type, $field_name);
    } else { 
        $this->assertHasCallout('success', 'Successfully created new administrator, unit_test');
        $this->assertHasDBField("SELECT * FROM admin WHERE username = 'unit_test'", 'email', 'unit@test.com');
    }

}

/**
 * Provider -- Create administrator 
 */
public function provider_create_admin()
{ 

    // Set legitimate vars
    $vars = array(
        'username' => 'unit_test',
        'password' => 'mypassword123',
        'confirm-password' => 'mypassword123',
        'full_name' => 'Unit Test',
        'email' => 'unit@test.com',
        'phone_country' => '1',
        'phone' => '5551234567',
        'require_2fa' => '0',
        'require_2fa_phone' => 0, 
        'language' => 'en',
        'timezone' => 'PST',
        'submit' => 'create'
    );

    // Set requests
    $results = array(
        array($vars, 'blank', 'username'),
        array($vars, 'blank', 'password'),
        array($vars, 'blank', 'full_name'),
        array($vars, 'blank', 'email'),
        array($vars, 'blank', 'language'),
        array($vars, 'blank', 'timezone'),
        array($vars, 'alphanum', 'username'),
        array($vars, 'email', 'email'),
        array($vars, '', ''), 
        array($vars, 'dupe', 'username')
    );

    // Add bogus variables
    $results[0][0]['username'] = '';
    $results[1][0]['password'] = '';
    $results[2][0]['full_name'] = '';
    $results[3][0]['email'] = '';
    $results[4][0]['language'] = '';
    $results[5][0]['timezone'] = '';
    $results[6][0]['username'] = 'dkg$*d Agiu4%g';
    $results[7][0]['email'] = 'testing_email';

    // Return
    return $results;

}

/**
 * Settings->Administrators page 
 */
public function test_page_admin_settings_admin()
{ 

    // Initialize
    io::remove_dir(SITE_PATH . '/etc/demo');

    // Ensure page loads
    $html = $this->http_request('admin/settings/admin');
    $this->assertPageTitle('Administrators');
    $this->assertHasTable('core:admin');
    $this->assertHasSubmit('create', 'Create New Administrator');
    $this->assertHasTableField('core:admin', 2, 'unit_test');

    // Get admin ID
    $admin_id = db::get_field("SELECT id FROM admin WHERE username = 'unit_test'");

    // Check manage admin page
    $html = $this->http_request('/admin/settings/admin_manage', 'GET', array(), array('admin_id' => $admin_id));
    $this->assertPageTitle('Manage Administrator');
    $this->assertHasSubmit('update', 'Update Administrator');

    // Set vars for update
    $vars = array(
        'username' => 'unit_test',
        'password' => 'test555',
        'confirm-password' => 'test555',
        'full_name' => 'Unit Test',
        'email' => 'update@test.com',
        'phone_country' => '1',
        'phone' => '5551234567',
        'require_2fa' => '0',
        'language' => 'en',
        'timezone' => 'PST',
        'submit' => 'update',
        'admin_id' => $admin_id
    );

    // Update administrator
    $html = $this->http_request('/admin/settings/admin', 'POST', $vars);
    $this->assertHasCallout('success', 'Successfully updated administrator details');
    $this->assertHasDBField("SELECT * FROM admin WHERE username = 'unit_test'", 'email', 'update@test.com');

    // Check password
    $password = db::get_field("SELECT password FROM admin WHERE id = %i", $admin_id);
    $this->assertTrue(password_verify('test555', base64_decode($password)));

    // Set delete vars
    $vars = array(
        'admin_id' => array($admin_id), 
        'submit' => 'delete'
    );

    // Send delete request
    $html = $this->http_request('admin/settings/admin', 'POST', $vars);
    $this->assertPageTitle('Administrators');
    $this->assertHasCallout('success', 'Successfully deleted');
    $this->assertNotHasTableField('core:admin', 1, 'unit_test');
    $this->assertNotHasDBRow("SELECT * FROM admin WHERE username = 'unit_test'");

}

/**
 * Admin - demo package falses
 */
public function test_admin_demo_package()
{

    // Add demo package
    io::create_dir(SITE_PATH . '/etc/demo');
    file_put_contents(SITE_PATH . '/etc/demo/package.php', ' ');
    $admin_id = db::get_field("SELECT id FROM admin WHERE username = %s", $_SERVER['apex_admin_username']);

    // Set vars for update
    $request = array(
        'username' => $_SERVER['apex_admin_username'], 
        'password' => 'test555',
        'confirm-password' => 'test555',
        'full_name' => 'Unit Test',
        'email' => 'update@test.com',
        'phone_country' => '1',
        'phone' => '5551234567',
        'require_2fa' => '0',
        'language' => 'en',
        'timezone' => 'PST',
        'submit' => 'update',
        'admin_id' => $admin_id
    );

    // Send http request
    $html = $this->http_request('admin/settings/admin', 'POST', $request);
    $this->assertPageTitle('Administrators');
    $this->assertHasCallout('error', 'Unable to modify this account');

    // Send delete request
    $admin = app::make(admin::class, ['id' => (int) $admin_id]);
    $ok = $admin->delete();
    $this->assertFalse($ok);

    // Send update_status request
    $ok = $admin->update_status('inactive');
    $this->assertFalse($ok);

    // Remove directory
    io::remove_dir(SITE_PATH . '/etc/demo');

}

/**
 * Notification menu 
 */
public function test_page_admin_settings_notifications()
{ 

    // Ensure page loads
    $html = $this->http_request('/admin/settings/notifications');
    $this->assertPageTitle('Notifications');
    $this->assertHasTable('core:notifications');
    $this->assertHasSubmit('create', 'Create E-Mail Notification');

    // Send invalid controller to get code ocverage
    $data = array(
        'html' => '', 
        'data' => array('controller' => 'some_junk_controller_that_will_never_exist')
    );
    $response = components::call('process', 'htmlfunc', 'notification_condition', 'core', '', $data);
    $this->assertNotEmpty($response);
    $this->assertStringContains($response, 'The notification controller');
    $this->assertStringContains($response, 'does not exist');

    // Send request to create e-mail notification
    $vars = array(
        'controller' => 'users',
        'submit' => 'create'
    );
    $html = $this->http_request('/admin/settings/notifications_create', 'POST', $vars);
    $this->assertPageTitle('Create Notification');
    $this->assertHasFormField('subject');
    $this->assertHasFormField('recipient');
    $this->assertHasFormField('cond_action');

    // Get administrator
    $admin_id = db::get_field("SELECT id FROM admin ORDER BY id LIMIT 0,1");

    // Set variables to create notification
    $vars = array(
        'controller' => 'users',
        'sender' => 'admin:' . $admin_id,
        'recipient' => 'user',
        'cond_action' => 'create',
        'cond_status' => 'active',
        'cond_group_id' => '',
        'content_type' => 'text/plain',
        'subject' => 'Test Subject 123',
        'contents' => 'This is a test message.',
        'submit' => 'create'
    );

    // Create notification
    $html = $this->http_request('/admin/settings/notifications', 'POST', $vars);
    $this->assertHasCallout('success', 'Successfully added new e-mail notification');
    //$this->assertHasTableField('core:notifications', 4, 'Test Subject 123');

    // Get notification ID
    $notification_id = db::get_field("SELECT id FROM notifications WHERE subject = 'Test Subject 123'");
    $this->assertnotfalse($notification_id, "Unable to find e-mail notification in database");

    // Get edit notification page
    $html = $this->http_request('/admin/settings/notifications_edit', 'GET', array(), array('notification_id' => $notification_id));
    $this->assertPageTitle('Edit Notification');
    $this->assertHasFormField('subject');
    $this->assertHasFormField('cond_action');
    $this->assertHasFormField('recipient');
    $this->assertHasSubmit('update', 'Update E-Mail Notification');

    // Set update vars
    $vars['subject'] = 'Update Test';
    $vars['submit'] = 'update';
    $vars['notification_id'] = $notification_id;

    // Edit notification
    $html = $this->http_request('/admin/settings/notifications', 'POST', $vars);
    $this->assertPageTitle('Notifications');
    $this->assertHasCallout('success', 'Successfully updated the e-mail notification');
    //$this->assertHasTableField('core:notifications', 4, 'Update Test');
    $this->assertHasDBField("SELECT * FROM notifications WHERE id = $notification_id", 'subject', 'Update Test');

    // Set vars to delete notification
    $vars = array(
    'table' => 'core:notifications',
    'id' => 'tbl_core_notifications',
    'notification_id' => array($notification_id)
    );

    // Delete notification
    $html = $this->http_request('/ajax/core/delete_rows', 'POST', $vars);
    $html = $this->http_request('/admin/settings/notifications');
    $this->assertPageTitle('Notifications');
    $this->assertHasTable('core:notifications');
    $this->assertNotHasTableField('core:notifications', 4, 'Update Test');

    // Ensure notification is deleted
    $row = db::get_row("SELECT * FROM notifications WHERE id = %i", $notification_id);
    $this->assertfalse($row, "Unable to delete the e-mail notification");

}

/**
 * Page -- Admin -> Settings -> Dashboard
 */
public function test_page_admin_settings_dashboard()
{

    // Ensure page loads
    $html = $this->http_request('admin/settings/dashboard');
    $this->assertPageTitle('Dashboard Settings');
    $this->assertPageContains('Manage Dashboards');
    $this->assertHasHeading(3, 'Personalized Dashboards');
    $this->assertHasTable('core:dashboard_profiles_items');
    $this->assertHasTable('core:dashboard_profiles');
    $this->assertHasSubmit('change', 'Change');
    $this->assertHasHeading(5, 'Add Dashboard Item');
    $this->assertHasSubmit('add_item', 'Add Dashboard Item');

    // Get database variables
    $profile_id = db::get_field("SELECT id FROM dashboard_profiles WHERE area = 'admin' AND is_default = 1");
    $item_id = db::get_field("SELECT id FROM dashboard_items WHERE area = 'admin' AND type = 'right' AND alias = 'blank'");

    // Set request
    $request = array(
        'dashboard' => $profile_id, 
        'item' => $item_id, 
        'submit' => 'add_item'
    );

    // Send http request
    $html = $this->http_request('admin/settings/dashboard', 'POST', $request);
    $this->assertPageTitle('Dashboard Settings');
    $this->assertHasCallout('success', 'Successfully added new dashboard item');

    // Delete item
    $item_id = db::get_field("SELECT id FROM dashboard_profiles_items WHERE profile_id = %i ORDER BY id DESC LIMIT 0,1", $profile_id);
    db::query("DELETE FROM dashboard_profiles_items WHERE id = %i", $item_id);

    // Create new dashboard profile
    $request = array(
        'admin_id' => 1, 
        'submit' => 'create_dashboard'
    );
    $html = $this->http_request('admin/settings/dashboard', 'POST', $request);
    $this->assertPageTitle('Dashboard Settings');
    $this->assertHasCallout('success', 'Successfully created new');


    // Get profile ID
    $profile_id = db::get_field("SELECT id FROM dashboard_profiles WHERE area = 'admin' AND userid = 1 AND is_default = 0");
    $this->assertNotFalse($profile_id);

    // Manage profile
    $html = $this->http_request('admin/settings/dashboard', 'GET', array(), array('profile_id' => $profile_id));
    $this->assertPageTitle('Dashboard Settings');

    // Change dashboard
    $request = array(
        'dashboard' => $profile_id, 
        'submit' => 'change'
    );
    $html = $this->http_request('admin/settings/dashboard', 'POST', $request);
    $this->assertPageTitle('Dashboard Settings');
    db::query("DELETE FROM dashboard_profiles WHERE id = %i", $profile_id);

}

/**
 * Maintenance->Package Manager page 
 */
public function test_page_admin_maintenance_package_manager()
{ 

    // Temporarily change core version
    $version = db::get_field("SELECT version FROM internal_packages WHERE alias = 'core'");
    db::query("UPDATE internal_packages SET version = '1.0.0' WHERE alias = 'core'");

    // Get latest version
        $app = app::get_instance();
    $client = $app->make(network::class);
    $upgrades = $client->check_upgrades();
    $core_version = $upgrades['core'] ?? '1.0.0';
    $new_version = '<b>v' . $core_version . '</b>';

    // Ensure page loads
    $html = $this->http_request('admin/maintenance/package_manager');
    db::query("UPDATE internal_packages SET version = '$version' WHERE alias = 'core'");

    // Initial checks
    $this->assertPageTitle('Package Manager');
    $this->assertHasHeading(3, 'Installed Packages');
    $this->assertHasHeading(3, 'Available Packages');
    $this->assertHasHeading(3, 'Repositories');
    $this->assertHasHeading(5, 'Existing Repositories');
    $this->assertHasHeading(5, 'Add New Repository');
    $this->assertHasFormField('repo_host');
    $this->assertHasFormField('repo_username');
    $this->assertHasFormField('repo_password');

    // Check data tables
    $this->assertHasTable('core:packages');
    $this->assertHasTable('core:available_packages');
    $this->assertHasTable('core_repos');
    $this->assertHasTableField('core:packages', 0, '<b>Core Framework v1.0.0</b>');
    $this->assertHasTableField('core:packages', 1, $new_version);
    //$this->assertHasTableField('core:available_packages', 0, 'bitcoin_block_explorer');

// Add an invalid repo
    $request = array(
        'repo_is_ssl' => 1, 
        'repo_host' => 'google.com', 
        'repo_username' => '',
        'repo_password' => '',
        'submit' => 'add_repo'
    );
    $html = $this->http_request('admin/maintenance/package_manager', 'POST', $request);
    $this->assertHasCallout('error', 'Test connection to repository failed');

    // Set new request to add repo
    // Add new repoy
    $request['repo_is_ssl'] = 0;
    $request['repo_host'] = app::_config('core:domain_name');
    db::query("UPDATE internal_repos SET is_active = 1");
    db::query("UPDATE internal_repos SET host = %s WHERE is_local = 1", app::_config('core:domain_name'));

    // Send http request
    $html = $this->http_request('admin/maintenance/package_manager', 'POST', $request);
    $this->assertPageTitle('Package Manager');
    $this->assertHasCallout('success', "Successfully added new repository");
    $this->assertHasTable('core:repos');
    $this->assertHasTableField('core:repos', 2, app::_config('core:domain_name'));

    // Update repo
    $repo_id = db::get_field("SELECT id FROM internal_repos WHERE host = %s ORDER BY id DESC LIMIT 0,1", app::_config('core:domain_name'));
    $html = $this->http_request('admin/maintenance/repo_manage', 'GET', array(), array('repo_id' => $repo_id));
    $this->assertPageTitle('Manage Repository');
    $this->assertHasHeading(3, 'Repo Details');
    $this->assertHasFormField('repo_username');
    $this->assertHasFormField('repo_password');
    $this->assertHasSubmit('update_repo', 'Update Repository');

    // Update the repo
    $request = array(
        'repo_id' => $repo_id,
        'repo_is_ssl' => 0,
        'repo_host' => app::_config('core:domain_name'),  
        'repo_username' => 'test',
        'repo_password' => 'test',
        'submit' => 'update_repo'
    );
    $html = $this->http_request('admin/maintenance/package_manager', 'POST', $request);
    $this->assertPageTitle('Package Manager');
    $this->assertHasCallout('success', "Successfully updated repository login details");

    // Delete repos

    // Delete repo
    db::query("DELETE FROM internal_repos WHERE id = %i", $repo_id);

}

/**
 * Test - Update local repo
 */
public function test_update_public_repo()
{

    // Get local repo
    if (!$row = db::get_row("SELECT * FROM internal_repos WHERE is_local = 1 ORDER BY id LIMIT 1")) { 
        throw new ApexEException('error', "No local repository found");
    }

    // Send http requset
    $html = $this->http_request('admin/maintenance/repo_manage', 'GET', array(), array('repo_id' => $row['id']));
    $this->assertPageTitle('Manage Repository');
    $this->assertHasFormField(array('repo_host', 'repo_alias', 'repo_name', 'repo_username', 'repo_password'));
    $this->assertHasSubmit('update_repo', 'Update Repository');

    // Set request
    $request = array(
        'repo_id' => $row['id'], 
        'repo_is_ssl' => 1, 
        'repo_host' => 'test.com', 
        'repo_alias' => 'public', 
        'repo_name' => '$this->assertEquals(', 
        'repo_description' => 'unit test', 
        'repo_username' => 'unit', 
        'repo_password' => 'test', 
        'submit' => 'update_repo'
    );

    // Send http request
    $html = $this->http_request('admin/maintenance/package_manager', 'POST', $request);
    $this->assertPageTitle('Package Manager');
    $this->assertHasCallout('success', 'Successfully updated repo');

    // Check repo database row
    $chk_row = db::get_idrow('internal_repos', $row['id']);
    $this->assertNotFalse($chk_row);
    $this->assertEquals('test.com', $chk_row['host']);
    $this->assertEquals('$this->assertEquals(', $chk_row['name']);
    $this->assertEquals('unit test', $chk_row['description']);
    $this->assertEquals('unit', encrypt::decrypt_basic($chk_row['username']));
    $this->assertEquals('test', encrypt::decrypt_basic($chk_row['password']));

    // REvert to old database info
    $repo_id = $row['id'];
    unset($row['id']);
    db::update('internal_repos', $row, "id = %i", $repo_id);

}

/**
 * Maintenance->Theme Manager menu 
 */
public function test_page_admin_maintenance_theme_manager()
{ 

    // Ensure page loads
    $html = $this->http_request('admin/maintenance/theme_manager');
    $this->assertPageTitle('Theme Manager');
    $this->assertHasHeading(3, 'Public Site');


}

/**
 * Maintenance->Backup Manager menu 
 */
public function test_page_admin_maintenance_backup_manager()
{ 

    // Set vars
    $vars = array(
        'backups_enable' => 1
    );

    // Set request
    $request = array(
        'submit' => 'update',
        'backups_db_interval_period' => 'H',
        'backups_db_interval_num' => 2,
        'backups_full_interval_period' => 'D',
        'backups_full_interval_num' => 5,
        'backups_retain_length_period' => 'W',
        'backups_retain_length_num' => 3
    );
    $request = array_merge($request, $vars);

    // Set extra vars to check
    $vars['backups_db_interval'] = 'H2';
    $vars['backups_full_interval'] = 'D5';
    $vars['backups_retain_length'] = 'W3';

    // Ensure page loads
    $html = $this->http_request('admin/maintenance/backup_manager');
    $this->assertPageTitle('Backup Manager');
    $this->assertHasHeading(3, 'Backup Details');
    $this->assertHasSubmit('update', 'Update Backup Settings');

    // Get current config vars
    $current_vars = app::getall_config();

    // Update vars
    $html = $this->http_request('admin/maintenance/backup_manager', 'POST', $request);
    $this->assertPageTitle('Backup Manager');
    $this->assertHasCallout('success', "Successfully updated backup settings");

    // Check config vars
    foreach ($vars as $key => $value) { 
        $chk = redis::hget('config', 'core:' . $key);
        $this->assertequals($value, $chk, "Did not properly update the config var $key to $value");
        app::update_config_var('core:' . $key, $current_vars['core:' . $key]);
    }

}

/**
 * Maintenance->Cron Manager page 
 */
public function test_page_admin_maintenance_cron_manager()
{ 

    // Ensure page loads
    $html = $this->http_request('admin/maintenance/cron_manager');
    $this->assertPageTitle('Cron Manager');
    $this->assertHasTable('core:crontab');

}

/**
 * CMS->Menus page 
 */
public function test_page_admin_cms_menus()
{ 

    // Ensure page loads
    $html = $this->http_request('admin/cms/menus');
    $this->assertPageTitle('Menus');
    $this->assertHasHeading(3, 'Public Site');
    $this->assertHasHeading(3, 'Member Area');
    $this->assertHasHeading(3, 'Add New Menu');
    $this->assertHasFormField(array('alias','name'));
    $this->assertHasSubmit('update_public', 'Update Public Site Menus');
    $this->assertHasSubmit('add_menu', 'Add New Menu');

    // Delete existing row, if exists
    db::query("DELETE FROM cms_menus WHERE alias IN ('unit_test', 'update_test') AND area = 'public'");

    // Set request to add menu
    $request = array(
        'area' => 'public', 
        'alias' => 'unit_test', 
        'name' => 'Unit Test', 
        'order_num' => 15, 
        'require_login' => 0, 
        'require_nologin' => 0, 
        'link_type' => 'internal', 
        'parent' => '', 
        'icon' => '', 
        'url' => '', 
        'submit' => 'add_menu'
    );

    // Send http request
    $html = $this->http_request('admin/cms/menus', 'POST', $request);
    $this->assertPageTitle('Menus');
    $this->assertHasCallout('success', 'Successfully added new menu');
    $this->assertHasTable('core:cms_menus');
    $this->assertHasTableField('core:cms_menus', 1, '/unit_test');

    // Check database row
    $row = db::get_row("SELECT * FROM cms_menus WHERE area = 'public' AND alias = 'unit_test'");
    $this->assertNotFalse($row);
    $this->assertEquals('public', $row['area']);
    $this->assertEquals('internal', $row['link_type']);
    $this->assertEquals('Unit Test', $row['name']);
    $this->assertEquals(1, (int) $row['is_active']);

    // HTTP request to manage menu
    $html = $this->http_request('admin/cms/menus_manage', 'GET', array(), array('menu_id' => $row['id']));
    $this->assertPageTitle('Manage Menu');
    $this->assertHasHeading(3, 'Menu Details');
    $this->assertHasFormField(array('alias','name'));
    $this->assertHasSubmit('update_menu', 'Update Menu');

    // Set request to add menu
    $request = array(
        'menu_id' => $row['id'], 
        'area' => 'public', 
        'alias' => 'update_test', 
        'name' => 'Update Test', 
        'order_num' => 15, 
        'require_login' => 0, 
        'require_nologin' => 0, 
        'link_type' => 'internal', 
        'parent' => '', 
        'icon' => '', 
        'url' => '', 
        'submit' => 'update_menu'
    );

    // Send HTTP request to update menu
    $html = $this->http_request('admin/cms/menus', 'POST', $request);
    $this->assertPageTitle('Menus');
    $this->assertHasCallout('success', 'Successfully updated menu');

    // Check database row
    $row = db::get_idrow('cms_menus', $row['id']);
    $this->assertNotFalse($row);
    $this->assertEquals('update_test', $row['alias']);
    $this->assertEquals('Update Test', $row['name']);
    $this->assertEquals(1, (int) $row['is_active']);

    // Deactivate menu
    $ids = db::get_column("SELECT id FROM cms_menus WHERE area = 'public' AND is_active = 1 AND id != %i", $row['id']);
    $request = array(
        'is_active' => $ids, 
        'delete' => array(), 
        'submit' => 'update_public'
    );

    // Add ordering to request
    $order_nums = db::get_hash("SELECT id,order_num FROM cms_menus WHERE area = 'public'");
    foreach ($order_nums as $id => $num) { 
        $request['order_' . $id] = $id == $row['id'] ? 20 : $num;
    }

    // Send http request
    $html = $this->http_request('admin/cms/menus', 'POST', $request);
    $this->assertPageTitle('Menus');
    $this->assertHasCallout('success', 'Successfully updated menus');

    // Check database row
    $row = db::get_row("SELECT * FROM cms_menus WHERE area = 'public' AND alias = 'update_test'");
    $this->assertNotFalse($row);
    $this->assertEquals(0, (int) $row['is_active']);
    $this->assertEquals(20, (int) $row['order_num']);

    // Delete menu
    $request['delete'] = array($row['id']);
    $html = $this->http_request('admin/cms/menus', 'POST', $request);
    $this->assertPageTitle('Menus');
    $this->assertHasCallout('success', 'Successfully updated menus');
    $row = db::get_row("SELECT * FROM cms_menus WHERE area = 'public' AND alias = 'update_test'");
    $this->assertFalse($row);

    // Load member area menu
    if ($menu_id = db::get_field("SELECT id FROM cms_menus WHERE area = 'members' ORDER BY RAND() LIMIT 0,1")) { 
        $html = $this->http_request('admin/cms/menus_manage', 'GET', array(), array('menu_id' => $menu_id));
        $this->assertPageTitle('Manage Menu');
    }

}

/**
 * Page -- Admin -> CMS -> Pages
 */
public function test_page_admin_cms_pages()
{

    // Ensure page loads
    $html = $this->http_request('admin/cms/pages');
    $this->assertPageTitle('Manage Pages');
    $this->assertHasHeading(3, 'Public Site');
    $this->assertHasHeading(3, 'Member Area');
    $this->assertHasTable('core:cms_pages');
    $this->assertHasSubmit('update_public', 'Update Public Site Pages');

    // Set request
    $request = array(
        'layout_public_index' => 'homepage', 
        'title_public_index' => 'Unit Test', 
        'submit' => 'update_public'
    );

    // Send http request
    $html = $this->http_request('admin/cms/pages', 'POST', $request);
    $this->assertPageTitle('Manage Pages');
    $this->assertHasCallout('success', 'Successfully updated the title'); 

    // Check database row
    $row = db::get_row("SELECT * FROM cms_pages WHERE area = 'public' AND filename = 'index'");
    $this->assertNotFalse($row);
    $this->assertEquals('homepage', $row['layout']);
    $this->assertEquals('Unit Test', $row['title']);

    // Check redis
    $this->assertEquals('homepage', redis::hget('cms:layouts', 'public/index'));
    $this->assertEquals('Unit Test', redis::hget('cms:titles', 'public/index'));

}

/**
 * Page - Admin -> CMS -> Placeholders
 */
public function test_page_admin_cms_placeholders()
{

    // Ensure page loads
    $html = $this->http_request('admin/cms/placeholders');
    $this->assertPageTitle('Placeholders');
    $this->assertHasHeading(3, 'Public Site');
    $this->assertHasHeading(3, 'Member Area');
    $this->assertHasTable('core:cms_placeholders');

    // Manage placeholder
    $html = $this->http_request('admin/cms/placeholders_manage', 'GET', array(), array('uri' => 'public/login'));
    $this->assertPageTitle('Manage Placeholder');
    $this->assertHasSubmit('update', 'Update Placeholders');

    // Set request
    $request = array(
        'uri' => 'public/login', 
        'contents_above_form' => 'Above Test', 
        'contents_below_form' => 'Below Test', 
        'submit' => 'update'
    );

    // Send http request
    $html = $this->http_request('admin/cms/placeholders', 'POST', $request);
    $this->assertPageTitle('Placeholders');
    $this->assertHasCallout('success', 'Successfully updated necessary');

    // Check database row
    $row = db::get_row("SELECT * FROM cms_placeholders WHERE uri = 'public/login' AND alias = 'below_form'");
    $this->assertNotFalse($row);
    $this->assertEquals('Below Test', $row['contents']);

    // Check redis
    $this->assertEquals('Above Test', redis::hget('cms:placeholders', 'public/login:above_form'));
    $this->assertEquals('Below Test', redis::hget('cms:placeholders', 'public/login:below_form'));

}

/**
 * logout
 */
public function test_logout()
{

    // Send http request
    $html = $this->http_request('admin/logout');
    $this->assertPageTitle('Logged Out');

}



}


