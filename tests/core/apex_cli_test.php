<?php
declare(strict_types = 1);

namespace tests\core;

use apex\app;
use apex\svc\db;
use apex\svc\redis;
use apex\svc\io;
use apex\svc\components;
use apex\app\sys\apex_cli;
use apex\app\pkg\package;
use apex\app\pkg\package_config;
use apex\app\pkg\upgrade;
use apex\users\user;
use apex\app\tests\test;


/**
 * Unit tests for the /apex.php CLI commands, and the /lib/apex_cli.php class. 
 *  
 */
class test_apex_cli extends test
{

/**
 * setUp 
 */
public function setUp():void
{ 

    // Get app, if needed
    if (!$app = app::get_instance()) { 
        $app = new app('test');
    }
    $this->app = $app;

    // Ensure repo is updated
    $response = $this->send_cli('update_repo', array(app::_config('core:domain_name'), $_SERVER['apex_test_username'], $_SERVER['apex_test_password']));

    // Set repo variables
    $this->repo_id = db::get_field("SELECT id FROM internal_repos WHERE is_local = 1 AND host = %s", app::_config('core:domain_name'));
    $this->pkg_alias = 'unit_test';

}


/**
 * Help 
 */
public function test_help()
{ 

    // Get response
    $response = $this->send_cli('help');
    $this->assertStringContains($response, 'create_package');

}

/**
 * List all packages from all repos 
 */
public function test_list_packages()
{ 

    // Send request
    $response = $this->send_cli('list_packages');

    // Check
    $vars = array('users', 'devkit', 'transaction', 'support', 'digitalocean');
    foreach ($vars as $var) { 
        $this->assertStringContains($response, $var, "Response from list_packages does not contain $var");
    }

}

/**
 * Search packages on all repos 
 */
public function test_search()
{ 

    // Search
    $terms = array('users', 'development', 'DigitalOcean');
    foreach ($terms as $term) { 
        $response = $this->send_cli('search', array($term));
        $this->assertStringNotContains($response, 'No packages found');
    }

    // Search for non-existent string
    $response = $this->send_cli('search', array('somegibberishthatwillneverexistinanypackage'));
    $this->assertStringContains($response, 'No packages found');

}

/**
 * Test add_repo command
 */
public function test_add_repo()
{

    // Delete demo user, if exists
    if ($userid = db::get_field("SELECT id FROM users WHERE username = %s", $_SERVER['apex_test_username'])) { 
        $user = app::make(user::class, ['id' => (int) $userid]);
    $user->delete();
    }

    // Get demo user
    $userid = $this->get_demo_user();

    // Send request
    $response = $this->send_cli('add_repo', array(app::_config('core:domain_name'), $_SERVER['apex_test_username'], $_SERVER['apex_test_password']));
    $this->assertStringContains($response, "Successfully added new repository", "Unable to add repository");

    // Get new repo ID
    $this->repo_id = db::get_field("SELECT id FROM internal_repos WHERE host = %s", app::_config('core:domain_name'));

}

/**
 * Create_Package 
 */
public function test_create_package()
{ 

    // Ensure package is removed
    $this->create_package_init_remove();

    // Send request
    $response = $this->send_cli('create_package', array($this->pkg_alias, $this->repo_id));
    $this->assertStringContains($response, 'Successfully created the new package', "Unable to create new package with alias, $this->pkg_alias");

    // Check directories
    $dirs = array(
        SITE_PATH . "/etc/$this->pkg_alias",
        SITE_PATH . "/etc/$this->pkg_alias/upgrades",
        SITE_PATH . "/src/$this->pkg_alias"
    );
    foreach ($dirs as $dir) { 
        $this->assertdirectoryexists($dir, "Creating package did not create necessary directories");
        $this->assertdirectoryiswritable($dir, "Creating package did not create writeable directories");
    }

    // Check files
    $files = array('package.php', 'install.sql', 'remove.sql', 'reset.sql');
    foreach ($files as $file) { 
        $this->assertfileexists(SITE_PATH . "/etc/$this->pkg_alias/$file", "Creating package did not create the /etc/$file file");
        $this->assertfileiswritable(SITE_PATH . "/etc/$this->pkg_alias/$file", "Package creation did not create writeable file at /etc/$file");
    }
    $this->assertFileContains(SITE_PATH . '/etc/unit_test/package.php', "class pkg_unit_test");

    // Check database row
    $row = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", $this->pkg_alias);
    $this->assertnotfalse($row, "Package creation did not add row into database with alias $this->pkg_alias");
    $this->assertisarray($row, "Creating package did not return array for database row");

    // Check database row values
    $chk_vars = array(
        'repo_id' => $this->repo_id,
        'access' => 'public', 
        'version' => '1.0.0',
        'prev_version' => '0.0.0',
        'alias' => $this->pkg_alias,
        'name' => $this->pkg_alias
    );
    foreach ($chk_vars as $key => $value) { 
        $this->assertarrayhaskey($key, $row, "Creating package database row does not have column $key");
        $this->assertequals($value, $row[$key], "Creating package database row column $key does not equal $value");
    }

}

/**
 * Remove package, if needed
 */
private function create_package_init_remove()
{

    // Ensure package does not exist in repo
    $repo_dir = SITE_PATH . '/storage/repo/public/' . $this->pkg_alias;
    if (is_dir($repo_dir)) { io::remove_dir($repo_dir); }
    db::query("DELETE FROM repo_packages WHERE type = 'package' AND alias = %s", $this->pkg_alias);
    db::query("DELETE FROM internal_upgrades WHERE package = %s", $this->pkg_alias);

    // Remove template files
    $rows = db::query("SELECT * FROM internal_components WHERE type = 'view' AND package = %s", $this->pkg_alias);
    foreach ($rows as $row) { 
        $files = components::get_all_files('view', $row['alias'], $row['package'], '');
        foreach ($files as $file) { 
            if (file_exists(SITE_PATH . '/' . $file)) { @unlink(SITE_PATH . '/' . $file); }
        }
    } 

    // Remove package and files, if needed
    db::query("DELETE FROM internal_packages WHERE alias = %s", $this->pkg_alias);
    db::query("DELETE FROM dashboard_items WHERE package = %s", $this->pkg_alias); 
    io::remove_dir(SITE_PATH . '/etc/' . $this->pkg_alias);
    io::remove_dir(SITE_PATH . '/src/' . $this->pkg_alias);
    io::remove_dir(SITE_PATH . '/tests/' . $this->pkg_alias);
    io::remove_dir(SITE_PATH . '/public/unit_test');
    io::remove_dir(SITE_PATH . '/docs/unit_test');

    // Delete test12345.tpl file
    if (file_exists(SITE_PATH . '/views/tpl/public/test12345.tpl')) { 
        @unlink(SITE_PATH . '/views/tpl/public/test12345.tpl');
    }

    // Remove redis
    $keys = redis::hkeys('config');
    foreach ($keys as $key) {
        if (!preg_match("/^" . $this->pkg_alias . ":/", $key)) { continue; } 
        redis::hdel('config', $key);
    }
    redis::del('test:package:before');
    redis::del('test:package:after');
    redis::del('test:package:remove');
    redis::del('test:package:reset');


}

/**
 * scan 
 */
public function test_scan()
{ 

    // Save files
    file_put_contents(SITE_PATH . "/etc/$this->pkg_alias/package.php", base64_decode('PD9waHAKCm5hbWVzcGFjZSBhcGV4OwoKdXNlIGFwZXhcYXBwOwp1c2UgYXBleFxzdmNcZGI7CnVzZSBhcGV4XHN2Y1xyZWRpczsKCi8qKgogKiBUZXN0IHBhY2thZ2UKICovCmNsYXNzIHBrZ191bml0X3Rlc3QgCnsKCiAgICAvLyBCYXNpYyBwYWNrYWdlIHZhcmlhYmxlcwogICAgcHVibGljICRhY2Nlc3MgPSAncHVibGljJzsKICAgIHB1YmxpYyAkbmFtZSA9ICd1bml0X3Rlc3QnOwogICAgcHVibGljICRkZXNjcmlwdGlvbiA9ICcnOwoKLyoqCiogVGhlIGNvbnN0cnVjdG9yIHRoYXQgZGVmaW5lcyB0aGUgdmFyaW91cyBjb25maWd1cmF0aW9uIAoqIGFycmF5cyBvZiB0aGUgcGFja2FnZSBzdWNoIGFzIGNvbmZpZyB2YXJzLCBoYXNoZXMsIAoqIG1lbnVzLCBhbmQgc28gb24uCioKKiBQbGVhc2Ugc2VlIHRoZSBBcGV4IGRvY3VtZW50YXRpb24gZm9yIGEgZnVsbCBleHBsYW5hdGlvbi4KKi8KcHVibGljIGZ1bmN0aW9uIF9fY29uc3RydWN0KCkgCnsKCi8vIENvbmZpZwokdGhpcy0+Y29uZmlnID0gYXJyYXkoCiAgICAnbmFtZScgPT4gJ1VuaXQgVGVzdCcsIAogICAgJ3Rlc3RfaWQnID0+IDQ1NgopOwoKLy8gSGFzaGVzCiR0aGlzLT5oYXNoID0gYXJyYXkoKTsKJHRoaXMtPmhhc2hbJ2hvdXNlcyddID0gYXJyYXkoCiAgICAnMWInID0+ICcxIEJlZHJvb20nLCAKICAgICcyYicgPT4gJzIgQmVkcm9vbScsIAogICAgJzNiMnMnID0+ICczIEJlZHJvb20gMiBTdG9yZXknCik7CgokdGhpcy0+aGFzaFsnZ2FtZXMnXSA9IGFycmF5KAogICAgJ3N0cmF0JyA9PiAnU3RyYXRlZ3knLCAKICAgICdhY3QnID0+ICdBY3Rpb24nLCAKICAgICdycCcgPT4gJ1JvbGUgUGxheWluZycKKTsKCgovLyBNZW51cwokdGhpcy0+bWVudXMgPSBhcnJheSgpOwokdGhpcy0+bWVudXNbXSA9IGFycmF5KAogICAgJ2FyZWEnID0+ICdhZG1pbicsIAogICAgJ3Bvc2l0aW9uJyA9PiAnYm90dG9tJywgCiAgICAndHlwZScgPT4gJ2hlYWRlcicsIAogICAgJ2FsaWFzJyA9PiAnaGRyX3VuaXRfdGVzdCcsIAogICAgJ25hbWUnID0+ICdVbml0IFRlc3QnCik7CgokdGhpcy0+bWVudXNbXSA9IGFycmF5KAogICAgJ2FyZWEnID0+ICdhZG1pbicsIAogICAgJ3Bvc2l0aW9uJyA9PiAnYWZ0ZXIgaGRyX3VuaXRfdGVzdCcsIAogICAgJ3R5cGUnID0+ICdwYXJlbnQnLCAKICAgICdpY29uJyA9PiAnZmEgZmEtZncgZmEtY29nJywgCiAgICAnYWxpYXMnID0+ICd0ZXN0aW5nJywgCiAgICAnbmFtZScgPT4gJ1Rlc3RpbmcnLCAKICAgICdtZW51cycgPT4gYXJyYXkoCiAgICAgICAgJ2NyZWF0ZScgPT4gJ0NyZWF0ZSBURXN0JywgCiAgICAgICAgJ2VkaXQnID0+ICdFZGl0IFRlc3QnLCAKICAgICAgICAnZGVsZXRlJyA9PiAnRGVsZXRlIFRFc3QnCiAgICApCik7CgokdGhpcy0+bWVudXNbXSA9IGFycmF5KAogICAgJ2FyZWEnID0+ICdhZG1pbicsIAogICAgJ3Bvc2l0aW9uJyA9PiAnYm90dG9tJywgCiAgICAncGFyZW50JyA9PiAnc2V0dGluZ3MnLCAKICAgICd0eXBlJyA9PiAnaW50ZXJuYWwnLCAKICAgICdhbGlhcycgPT4gJ3VuaXRfdGVzdCcsIAogICAgJ25hbWUnID0+ICdVbml0IFRlc3QnCik7CgokdGhpcy0+ZXh0X2ZpbGVzID0gYXJyYXkoCiAgICAnc3JjL3VuaXRfdGVzdC50eHQnLCAKICAgICdwdWJsaWMvdW5pdF90ZXN0LyonCik7CgokdGhpcy0+cGxhY2Vob2xkZXJzID0gYXJyYXkoCiAgICAncHVibGljL3VuaXRfdGVzdCcgPT4gYXJyYXkoJ2Fib3ZlX2Zvcm0nLCAnYmVsb3dfZm9ybScpCik7CgokdGhpcy0+Ym94bGlzdHMgPSBhcnJheSgKICAgIGFycmF5KAogICAgICAgICdhbGlhcycgPT4gJ3VuaXRfdGVzdDpzZXR0aW5ncycsIAogICAgICAgICdocmVmJyA9PiAnYWRtaW4vc2V0dGluZ3MvdW5pdF90ZXN0X2dlbmVyYWwnLCAKICAgICAgICAndGl0bGUnID0+ICdHZW5lcmFsIFNFdHRpbmdzJywgCiAgICAgICAgJ2Rlc2NyaXB0aW9uJyA9PiAnVGhlIHRlc3QgZ2VuZXJhbCBzZXR0aW5ncycKICAgICksIAogICAgYXJyYXkoCiAgICAgICAgJ2FsaWFzJyA9PiAndW5pdF90ZXN0OnNldHRpbmdzJywgCiAgICAgICAgJ2hyZWYnID0+ICdhZG1pbi9zZXR0aW5ncy91bml0X3Rlc3RfZGVsZXRlYWxsJywgCiAgICAgICAgJ3RpdGxlJyA9PiAnRGVsZXRlIEFMbCcsIAogICAgICAgICdkZXNjcmlwdGlvbicgPT4gJ0RlbGV0ZSBhbGwgdGhlIHVuaXQgdGVzdHMnCiAgICApCik7CgovLyBEYXNoYm9hcmQgaXRlbXMKJHRoaXMtPmRhc2hib2FyZF9pdGVtcyA9IGFycmF5KAogICAgYXJyYXkoCiAgICAgICAgJ2FyZWEnID0+ICdhZG1pbicsIAogICAgICAgICd0eXBlJyA9PiAndG9wJywgCiAgICAgICAgJ2RpdmlkJyA9PiAndW5pdF90ZXN0JywgCiAgICAgICAgJ3BhbmVsX2NsYXNzJyA9PiAncGFuZWwgYmctdGVhbC00MDAnLCAKICAgICAgICAnaXNfZGVmYXVsdCcgPT4gMCwgCiAgICAgICAgJ2FsaWFzJyA9PiAndW5pdF90ZXN0JywgCiAgICAgICAgJ3RpdGxlJyA9PiAnVW5pdCBUZXN0JywgCiAgICAgICAgJ2Rlc2NyaXB0aW9uJyA9PiAndW5pdCB0ZXN0IGRlc2NyaXB0aW9uJwogICAgKSwKICAgIGFycmF5KAogICAgICAgICdhcmVhJyA9PiAnYWRtaW4nLCAKICAgICAgICAndHlwZScgPT4gJ3JpZ2h0JywgCiAgICAgICAgJ2RpdmlkJyA9PiAndW5pdF90ZXN0X2xlZnQnLCAKICAgICAgICAncGFuZWxfY2xhc3MnID0+ICdwYW5lbCBiZy10ZWFsLTQwMCcsIAogICAgICAgICdpc19kZWZhdWx0JyA9PiAwLCAKICAgICAgICAnYWxpYXMnID0+ICd1bml0X3Rlc3RfbGVmdCcsIAogICAgICAgICd0aXRsZScgPT4gJ1VuaXQgVGVzdCBMZWZ0JywgCiAgICAgICAgJ2Rlc2NyaXB0aW9uJyA9PiAndW5pdCB0ZXN0IGxlZnQgZGVzY3JpcHRpb24nCiAgICApIAopOwoKLy8gTm90aWZpY2F0aW9ucwokdGhpcy0+bm90aWZpY2F0aW9ucyA9IGFycmF5KCk7CiR0aGlzLT5ub3RpZmljYXRpb25zW10gPSBhcnJheSgKICAgICdjb250cm9sbGVyJyA9PiAnc3lzdGVtJywgCiAgICAnc2VuZGVyJyA9PiAnYWRtaW46MScsIAogICAgJ3JlY2lwaWVudCcgPT4gJ3VzZXInLCAKICAgICdjb250ZW50X3R5cGUnID0+ICd0ZXh0L3BsYWluJywgCiAgICAnc3ViamVjdCcgPT4gJ1VuaXQgVGVzdCcsIAogICAgJ2NvbnRlbnRzJyA9PiAnJywgCiAgICAnY29uZF9hY3Rpb24nID0+ICcyZmEnCik7CgovLyBDb21wb3NlciBkZXBlbmRlbmRpY2VzCiR0aGlzLT5jb21wb3Nlcl9kZXBlbmRlbmNpZXMgPSBhcnJheSgKICAgICd1bml0L3Rlc3QnID0+ICcqJwopOwoKLy8gRGVwZW5kZW5jaWVzCiR0aGlzLT5kZXBlbmRlbmNpZXMgPSBhcnJheSgndXNlcnMnKTsKCgoKCgp9CgovKioKICogSW5zdGFsbCBiZWZvcmUKICovCnB1YmxpYyBmdW5jdGlvbiBpbnN0YWxsX2JlZm9yZSgpCnsKCiAgICByZWRpczo6c2V0KCd0ZXN0OnBhY2thZ2U6YmVmb3JlJywgJ3VuaXRfdGVzdCcpOwoKfQoKLyoqCiAqIGluc3RhbGwgYWZ0ZXIKICovCnB1YmxpYyBmdW5jdGlvbiBpbnN0YWxsX2FmdGVyKCkKewogICAgcmVkaXM6OnNldCgndGVzdDpwYWNrYWdlOmFmdGVyJywgJ3VuaXRfdGVzdCcpOwp9CgovKioKICogUmVtb3ZlCiAqLwpwdWJsaWMgZnVuY3Rpb24gcmVtb3ZlKCkKewogICAgcmVkaXM6OnNldCgndGVzdDpwYWNrYWdlOnJlbW92ZScsICd1bml0X3Rlc3QnKTsKfQoKLyoqCiAqIFJlc2V0CiAqLwpwdWJsaWMgZnVuY3Rpb24gcmVzZXQoKQp7CiAgICByZWRpczo6c2V0KCd0ZXN0OnBhY2thZ2U6cmVzZXQnLCAndW5pdF90ZXN0Jyk7Cn0KCgoKCn0KCgoK'));

    // Save external files
    io::create_dir(SITE_PATH . '/public/unit_test');
    file_put_contents(SITE_PATH . '/public/unit_test/t1.txt', "test one");
    file_put_contents(SITE_PATH . '/public/unit_test/t2.txt', "test two");
    file_put_contents(SITE_PATH . '/src/unit_test.txt', "just a test");
    io::create_dir(SITE_PATH . '/docs/unit_test');
    file_put_contents(SITE_PATH . '/docs/unit_test/index.md', "The unit test documentation");

    // save /src/tpl/ directory
    io::create_dir(SITE_PATH . '/src/unit_test/tpl/public');
    file_put_contents(SITE_PATH . '/src/unit_test/tpl/public/test12345.tpl', "\n<h1>Unit Test</h1>\n\n");

    // Scan
    $response = $this->send_cli('scan', array($this->pkg_alias));
    $this->assertStringContains($response, "Succesfully scanned the package");

    // Check package configuration
    $this->check_package_configuration();

    // Save updated package.php file, and scan again
    file_put_contents(SITE_PATH . "/etc/$this->pkg_alias/package2.php", base64_decode('PD9waHAKCm5hbWVzcGFjZSBhcGV4OwoKdXNlIGFwZXhcYXBwOwp1c2UgYXBleFxzdmNcZGI7CnVzZSBhcGV4XHN2Y1xyZWRpczsKCgovKioKICogdGVzdCBwYWNrYWdlCiAqLwpjbGFzcyBwa2dfdW5pdF90ZXN0MiAKewoKICAgIC8vIEJhc2ljIHBhY2thZ2UgdmFyaWFibGVzCiAgICBwdWJsaWMgJGFjY2VzcyA9ICdwdWJsaWMnOwogICAgcHVibGljICRuYW1lID0gJ3VuaXRfdGVzdCc7CiAgICBwdWJsaWMgJGRlc2NyaXB0aW9uID0gJyc7CgovKioKKiBUaGUgY29uc3RydWN0b3IgdGhhdCBkZWZpbmVzIHRoZSB2YXJpb3VzIGNvbmZpZ3VyYXRpb24gCiogYXJyYXlzIG9mIHRoZSBwYWNrYWdlIHN1Y2ggYXMgY29uZmlnIHZhcnMsIGhhc2hlcywgCiogbWVudXMsIGFuZCBzbyBvbi4KKgoqIFBsZWFzZSBzZWUgdGhlIEFwZXggZG9jdW1lbnRhdGlvbiBmb3IgYSBmdWxsIGV4cGxhbmF0aW9uLgoqLwpwdWJsaWMgZnVuY3Rpb24gX19jb25zdHJ1Y3QoKSAKewoKLy8gQ29uZmlnCiR0aGlzLT5jb25maWcgPSBhcnJheSgKICAgICduYW1lJyA9PiAndXBkYXRlIFRlc3QnLCAKICAgICd1cGRhdGVfdGVzdCcgPT4gJ3llcycKKTsKCi8vIEhhc2hlcwokdGhpcy0+aGFzaCA9IGFycmF5KCk7CiR0aGlzLT5oYXNoWydob3VzZXMnXSA9IGFycmF5KAogICAgJzFiJyA9PiAnMSBCZWRyb29tLCAxIEJhdGgnLCAKICAgICc0YicgPT4gJzQgQmVkcm9vbXMnLCAKICAgICczYjJzJyA9PiAnMyBCZWRyb29tIDIgU3RvcmV5JwopOwoKLy8gTWVudXMKJHRoaXMtPm1lbnVzID0gYXJyYXkoKTsKJHRoaXMtPm1lbnVzW10gPSBhcnJheSgKICAgICdhcmVhJyA9PiAnYWRtaW4nLCAKICAgICdwb3NpdGlvbicgPT4gJ2JvdHRvbScsIAogICAgJ3R5cGUnID0+ICdoZWFkZXInLCAKICAgICdhbGlhcycgPT4gJ2hkcl91bml0X3Rlc3QnLCAKICAgICduYW1lJyA9PiAnVW5pdCBUZXN0JwopOwoKJHRoaXMtPm1lbnVzW10gPSBhcnJheSgKICAgICdhcmVhJyA9PiAnYWRtaW4nLCAKICAgICdwb3NpdGlvbicgPT4gJ2FmdGVyIGhkcl91bml0X3Rlc3QnLCAKICAgICd0eXBlJyA9PiAncGFyZW50JywgCiAgICAnaWNvbicgPT4gJ2ZhIGZhLWZ3IGZhLWNvZycsIAogICAgJ2FsaWFzJyA9PiAndGVzdGluZycsIAogICAgJ25hbWUnID0+ICdUZXN0aW5nJywgCiAgICAnbWVudXMnID0+IGFycmF5KAogICAgICAgICdjcmVhdGUnID0+ICdVcGRhdGVkIE5hbWUnLCAKICAgICAgICAnZGVsZXRlJyA9PiAnRGVsZXRlIFRFc3QnLCAKICAgICAgICAndmlld2FsbCcgPT4gJ1ZpZXcgQUxsIFRlc3QnCiAgICApCik7CgoKJHRoaXMtPmV4dF9maWxlcyA9IGFycmF5KAogICAgJ3NyYy91bml0X3Rlc3QudHh0JwopOwoKJHRoaXMtPnBsYWNlaG9sZGVycyA9IGFycmF5KAogICAgJ3B1YmxpYy91bml0X3Rlc3QnID0+IGFycmF5KCAnYmVsb3dfZm9ybScsICd0b3BfcGFnZScsICdib3R0b21fcGFnZScpCik7CgokdGhpcy0+Ym94bGlzdHMgPSBhcnJheSgKICAgIGFycmF5KAogICAgICAgICdhbGlhcycgPT4gJ3VuaXRfdGVzdDpzZXR0aW5ncycsIAogICAgICAgICdocmVmJyA9PiAnYWRtaW4vc2V0dGluZ3MvdW5pdF90ZXN0X292ZXJ2aWV3JywgCiAgICAgICAgJ3RpdGxlJyA9PiAnT3ZlcnZpZXcgU2V0dGluZ3MnLCAKICAgICAgICAnZGVzY3JpcHRpb24nID0+ICdUaGUgdGVzdCBnZW5lcmFsIHNldHRpbmdzJwogICAgKSwgCiAgICBhcnJheSgKICAgICAgICAnYWxpYXMnID0+ICd1bml0X3Rlc3Q6c2V0dGluZ3MnLCAKICAgICAgICAnaHJlZicgPT4gJ2FkbWluL3NldHRpbmdzL3VuaXRfdGVzdF9kZWxldGVhbGwnLCAKICAgICAgICAndGl0bGUnID0+ICdVcGRhdGUgVGVzdCcsIAogICAgICAgICdkZXNjcmlwdGlvbicgPT4gJ0RlbGV0ZSBhbGwgdGhlIHVuaXQgdGVzdHMnCiAgICApCik7CgovLyBEYXNoYm9hcmQgaXRlbXMKJHRoaXMtPmRhc2hib2FyZF9pdGVtcyA9IGFycmF5KAogICAgYXJyYXkoCiAgICAgICAgJ2FyZWEnID0+ICdhZG1pbicsIAogICAgICAgICd0eXBlJyA9PiAndG9wJywgCiAgICAgICAgJ2RpdmlkJyA9PiAndW5pdF90ZXN0JywgCiAgICAgICAgJ3BhbmVsX2NsYXNzJyA9PiAncGFuZWwgYmctdGVhbC00MDAnLCAKICAgICAgICAnaXNfZGVmYXVsdCcgPT4gMCwgCiAgICAgICAgJ2FsaWFzJyA9PiAndW5pdF90ZXN0JywgCiAgICAgICAgJ3RpdGxlJyA9PiAnVXBkYXRlIFRlc3QnLCAKICAgICAgICAnZGVzY3JpcHRpb24nID0+ICd1bml0IHRlc3QgZGVzY3JpcHRpb24nCiAgICApCik7CgoKCgoKfQoKfQogICAKCg=='));
    require_once(SITE_PATH . "/etc/$this->pkg_alias/package2.php");
    $new_pkg = new \apex\pkg_unit_test2();

    // Load existing package
    $pkg_client = new package_config($this->pkg_alias);
    //$pkg = $pkg_client->load();

    // Install new configuration
    $pkg_client->install_configuration($new_pkg);

    // Ensure 'name' config variable didn't change
    $value = db::get_field("SELECT value FROM internal_components WHERE type = 'config' AND package = %s AND alias = 'name'", $this->pkg_alias);
    $value2 = redis::hget('config', $this->pkg_alias . ':name');
    $this->assertnotfalse($value, "The config variable 'name' disappeared!");
    $this->assertnotnull($value2, "The configuration var 'name' disappeared from redis!");
    $this->assertequals('Unit Test', $value, "The config var 'name' value changed, and it wasn't supposed to!");
    $this->assertequals('Unit Test', $value2, "The configuration var 'name' value changed in the redis database, and it wasn't supposed to!");
    app::update_config_var($this->pkg_alias . ':name', 'update_test');

    // Make sure 'test_id' config var was deleted
    $value = db::get_field("SELECT value FROM internal_components WHERE type = 'config' AND package = %s AND alias = 'test_id'", $this->pkg_alias);
    $value2 = redis::hget('config', $this->pkg_alias . ':test_id');
    $this->assertfalse($value, "The config var 'test_id' was not deleted from mySQL!");
    $this->assertfalse($value2, "The config var 'test_id' was not deleted from redis!");

    // Check hashes
    $var1 = db::get_field("SELECT id FROM internal_components WHERE type = 'hash_var' AND package = %s AND parent = 'houses' AND alias = '2b'", $this->pkg_alias);
    $name = db::get_field("SELECT value FROM internal_components WHERE type = 'hash_var' AND package = %s AND parent = 'houses' AND alias = '1b'", $this->pkg_alias);
    $this->assertfalse($var1, "Did not delete the '2bd' hash var from the 'houses' hash!");
    $this->assertnotfalse($name, "The '1bd' hash var was deleted from the 'houses' hash, and it wasn't supposed to!");
    $this->assertequals('1 Bedroom, 1 Bath', $name, "The '1bd' hash_var name within the 'houses' array wasn't updated!");

    // Check hash from redis
    $vars = json_decode(redis::hget('hash', $this->pkg_alias . ':houses'), true);
    $this->assertarraynothaskey('2b', $vars, "Did not delete the '2b' hash var from the 'houses' hash in  redis!");
    $this->assertarrayhaskey('1b', $vars, "The '1b' hash_var was deleted from the 'houses' hash within redis!");
    $this->assertequals('1 Bedroom, 1 Bath', $vars['1b'], "The '1b' hash_var within 'houses' hash wasn't updated in redis!");

    // Ensure 'games' hash was deleted
    $row = db::get_row("SELECT * FROM internal_components WHERE package = %s AND type = 'hash' AND alias = 'games'", $this->pkg_alias);
    $this->assertFalse($row);
    $this->assertFalse(redis::hget('hash', 'unit_test:games'));


    // Ensure menus are deleted
    $menu1 = db::get_field("SELECT id FROM cms_menus WHERE area = 'admin' AND parent = 'settings' AND alias = 'unit_test'");
    $menu2 = db::get_field("SELECT id FROM cms_menus WHERE area = 'admin' AND parent = 'testing' AND alias = 'edit'");
    $name = db::get_field("SELECT name FROM cms_menus WHERE area = 'admin' AND parent = 'testing' AND alias = 'create'");
    $this->assertfalse($menu1, "The Settings->Unit Tests menu was not deleted!");
    $this->assertfalse($menu2, "The Testing->Edit Test menu was not delted!");
    $this->assertnotfalse($name, "Unable to get name of Settings->Create Test menu");
    $this->assertequals('Updated Name', $name, "The Testing->Create Test menu was not updated to 'Updated Name'");

    // Placeholders
    $var1 = db::get_field("SELECT id FROM cms_placeholders WHERE package = %s AND uri = 'public/unit_test' AND alias = 'above_form'", $this->pkg_alias);
    $var2 = db::get_field("SELECT id FROM cms_placeholders WHERE package = %s AND uri = 'public/unit_test' AND alias = 'below_form'", $this->pkg_alias);
    $this->assertfalse($var1, "The placeholder 'above_form' was not deleted, and should have been!");
    $this->assertnotfalse($var2, "The placeholder 'below_form' was deleted, and it wasn't supposed to be!");

    // Boxlists
    $exists = db::get_field("SELECT id FROM internal_boxlists WHERE package = 'unit_test' AND alias = 'settings' AND href = 'admin/settings/unit_test_general'");
    $title = db::get_field("SELECT title FROM internal_boxlists WHERE package = 'unit_test' AND alias = 'settings' AND href = 'admin/settings/unit_test_deleteall'");
    $this->assertfalse($exists, "The 'general' boxlists was not deleted, and should have been!");
    $this->assertnotfalse($title, "The deleteall boxlists was deleted, and it shouldn't have been!");
    $this->assertequals('Update Test', $title, "The deleteall boxlist title was not updated as needed");

    // Dashboard items
    $row = db::get_row("SELECT * FROM dashboard_items WHERE package = 'unit_test' AND type = 'top'");
    $this->assertNotFalse($row);
    $this->assertEquals('Update Test', $row['title']);
    $this->assertFalse(db::get_row("SELECT * FROM dashboard_profiles_items WHERE package = 'unit_test' AND type = 'left'"));

    // Check package configuration
    $this->check_package_configuration($new_pkg, true);

}

/**
 * Check package configuration 
 *
 * Check package configuration.  Checks the /etc/PACKAGE/package.php config 
 * file, and ensures all config vars, hashes, menus, and everything else 
 * matches up with what is in the database and redis 
 */
protected function check_package_configuration($pkg = '', $is_updated = false)
{ 

    // Load package, if needed
    if (!is_object($pkg)) { 
        $pkg_client = new package_config($this->pkg_alias);
        $pkg = $pkg_client->load();
    }

    // Go through config vars, if not updating
    if ($is_updated === false) { 

        foreach ($pkg->config as $key => $value) { 

            // CHeck database
            $row = db::get_row("SELECT * FROM internal_components WHERE type = 'config' AND package = 'unit_test' AND alias = %s", $key);
            $this->assertnotfalse($row, "Unable to get config row from database for $key");
            $this->assertisarray($row, "Unable to get database row for config variable $key");
            $this->assertarrayhaskey('value', $row, "Database row for config variable $key does not contain 'value' column");
            $this->assertequals($row['value'], $value, "Configuration variable $key does not have value $value");

            // Check redis
            $data = redis::hget('config', 'unit_test:' . $key);
            $this->assertnotnull($data, "Configuration variable $key does not exist in redis");
            $this->assertequals($data, $value, "Configuration variable $key does not have value $value within redis");
        }
    }

    // Check hashes
    foreach ($pkg->hash as $hash_alias => $vars) { 

        // Check database
        $row = db::get_row("SELECT * FROM internal_components WHERE type = 'hash' AND package = 'unit_test' AND parent = '' AND alias = %s", $hash_alias);
        $this->assertnotfalse($row, "Unable to find hash in database with alias $hash_alias");
        $this->assertisarray($row, "Did not receive an array for database row when retrieving hash of alias $hash_alias");
        $this->assertarrayhaskey('alias', $row, "The database row for hash does not have 'alias' column");
        $this->assertequals($row['alias'], $hash_alias, "Invalid database row retrieved for hash alias $hash_alias");

        // Check redis
        $data = redis::hget('hash', 'unit_test:' . $hash_alias);
        $this->assertnotnull($data, "Hash does not exist in redis with alias $hash_alias");
        $this->assertnotempty($data, "Hash does not exist in redis with alias $hash_alias");
        $json_vars = json_decode($data, true);

        // CHeck redis component
        $this->check_redis_component('hash', $hash_alias, $this->pkg_alias, '');

        // Go through variables
        $chk_json = array();
        foreach ($vars as $key => $value) { 

            // Check database row
            $row = db::get_row("SELECT * FROM internal_components WHERE type = 'hash_var' AND package = 'unit_test' AND parent = %s AND alias = %s", $hash_alias, $key);
            $this->assertnotfalse($row, "Unable to retrieve hash_var from database with hash alias $hash_alias, variable $key");
            $this->assertisarray($row, "Unable to retrieve hash_var from database with hash alias $hash_alias, variable $key");
            $this->assertarrayhaskey('value', $row, "The row for hash variable does not have 'value' column, hash alias $hash_alias and variable $key");
            $this->assertequals($row['value'], $value, "The hash variable does not have the correct value, hash alias: $hash_alias, variable: $key, value: $value");

            // Check JSON
            $chk_json[$key] = $value;
            $this->assertarrayhaskey($key, $json_vars, "JSON jash of alias $hash_alias does not contain the variable $key");
            $this->assertequals($json_vars[$key], $value, "JSON hash variable is incorrect, hash alias $hash_alias, variable $key, value $value");

            // Check redis component
            $this->check_redis_component('hash_var', $key, $this->pkg_alias, $hash_alias);
        }

        // Check JSON
        $this->assertJsonStringEqualsJsonString(json_encode($chk_json), $data, "JSON strings do not match for hash alias $hash_alias");
    }

    // Check menus
    foreach ($pkg->menus as $vars) { 

        // Set variables
        $parent = $vars['parent'] ?? '';
        $submenus = $vars['menus'] ?? array();

        // Get row
        $row = db::get_row("SELECT * FROM cms_menus WHERE package = %s AND area = %s AND parent = %s AND alias = %s", $this->pkg_alias, $vars['area'], $parent, $vars['alias']);
        $this->assertnotfalse($row, "Unable to get menu row, area: $vars[area], parent: $parent, alias: $vars[alias]");
        $this->assertisarray($row, "Unable to get menu row, area: $vars[area], parent: $parent, alias: $vars[alias]");
        $this->assertequals($vars['type'], $row['link_type'], "Menu does not have correct type, alias: $vars[alias], type: $vars[type]");
        $this->assertequals($vars['name'], $row['name'], "Menu does not have correct name, alias: $vars[alias], name: $vars[name]");

        // Check submenus
        foreach ($submenus as $alias => $name) { 
            $row = db::get_row("SELECT * FROM cms_menus WHERE package = %s AND area = %s AND parent = %s AND alias = %s", $this->pkg_alias, $vars['area'], $vars['alias'], $alias);
            $this->assertnotfalse($row, "Submenu does not exist, parent: $vars[alias], alias: $alias");
            $this->assertisarray($row, "Submenu does not exist, parent: $vars[alias], alias: $alias");
            $this->assertequals($name, $row['name'], "Submenu does not have correct name, parent: $vars[alias], alias: $alias, name: $name");
        }
    }

    // Check placeholders
    foreach ($pkg->placeholders as $uri => $vars) { 
        foreach ($vars as $var) { 

            $row = db::get_row("SELECT * FROM cms_placeholders WHERE package = %s AND uri = %s AND alias = %s", $this->pkg_alias, $uri, $var);
            $this->assertnotfalse($row, "Placeholder does not exist with URI: $uri and alias: $var");
            $this->assertisarray($row, "Placeholder does not exist with URI: $uri and alias: $var");
        }
    }

    // Check boxlists
    foreach ($pkg->boxlists as $vars) { 
        list($package, $alias) = explode(':', $vars['alias'], 2);

        $row = db::get_row("SELECT * FROM internal_boxlists WHERE package = %s AND alias = %s AND href = %s", $package, $alias, $vars['href']);
        $this->assertnotfalse($row, "Boxlist does not exist, package: $package, alias: $alias");
        $this->assertisarray($row, "Boxlist does not exist, package: $package, alias: $alias");
        $this->assertequals($vars['title'], $row['title'], "Boxlists has incorrect title, package: $package, alias: $alias, title: $vars[title]");
    }

}

/**
 * Check redis for component 
 */
protected function check_redis_component($type, $alias, $package, $parent)
{ 

    // Check
    $line = implode(":", array($type, $package, $parent, $alias));
    $ok = redis::sismember('config:components', $line);
    $this->assertnotnull($ok, "Component does not exist in redis, $line");
    $this->assertequals(1, $ok, "Component does not exist in redis, $line");

    // Check components_package
    $chk = $type . ':' . $alias;
    $var = redis::hget('config:components_package', $chk);
    $this->assertnotnull($var, "Component does not exists in redis packages hash, $chk");
    if ($var != 2 && $var != $this->pkg_alias) { 
        $this->assertequals($this->pkg_alias, $var, "Component is invalid in redis packages hash, $chk -- $var");
    } else { $this->asserttrue(true); }

    // Check from /src/core/components.php libary
    $comp_alias = $parent == '' ? $package . ':' . $alias : $package . ':' . $parent . ':' . $alias;
    $ok = components::check($type, $comp_alias);
    $this->assertnotfalse($ok, "Component does not check as true in library, type: $type, alias: $comp_alias");

}

/**
 * create components 
 *      @dataProvider provider_create 
 */
public function test_create($type, $comp_alias, $owner, $files)
{ 

    // Create
    $response = $this->send_cli('create', array($type, $comp_alias, $owner));
    $this->assertStringContains($response, "Successfully created");

    // Verify component
    $this->verify_component_created($type, $comp_alias, $owner, $files);

}

/**
 * verify component was created correct 
 */
protected function verify_component_created($type, $comp_alias, $owner, $files)
{ 
    if ($type == 'worker') { sleep(1); }

    // Check files
    foreach ($files as $file => $text) { 
        $this->assertfileexists(SITE_PATH . '/' . $file, "Component file did not get created type: $type, comp alias: $comp_alias, file: $file");
        $this->assertfileiswritable(SITE_PATH . '/' . $file, "Component file did not get created type: $type, comp alias: $comp_alias, file: $file");
        $this->assertFileContains(SITE_PATH . '/' . $file, $text, "Component file does not contain necessary text, $file");
    }

    // Check component
    if (!list($package, $parent, $alias) = components::check($type, $comp_alias)) { 
        $this->asserttrue(false, "Component does not exist via check() method, type: $type, comp alias: $comp_alias");
    } else { $this->asserttrue(true); }

    // Check if we need to load component
    $ok = true;
    if ($type == 'controller' && $parent == '') { $ok = false; }
    elseif (in_array($type, array('lib', 'view', 'tabpage', 'test'))) { $ok = false; }

    // Load component, if needed
    if ($ok === true) { 
        if (!$client = components::load($type, $alias, $package, $parent)) { 
            $this->asserttrue(false, "Unable to load component, type: $type, package: $package, parent: $parent, alias: $alias");
        } else { $this->asserttrue(true); }
    }

    // Check PHP file
    $php_file = components::get_file($type, $alias, $package, $parent);
    if ($php_file != '' && isset($files[0])) { 
        $this->assertequals($files[0], $php_file, "Component PHP files do not match, $files[0]");
    }

    // Get .tpl file
    $tpl_file = components::get_tpl_file($type, $alias, $package, $parent);
    if ($tpl_file != '' && isset($files[1])) { 
        $this->assertequals($tpl_file, $files[1], "Component TPL file does not match, $tpl_file");
    }

    // Check mySQL database row
    $row = db::get_row("SELECT * FROM internal_components WHERE type = %s AND package = %s AND parent = %s AND alias = %s", $type, $package, $parent, $alias);
    $this->assertnotfalse($row, "Component row does not exist in mySQL, type: $type, package: $package, parent: $parent, alias: $alias");
    $this->assertisarray($row, "Component row does not exist in mySQL, type: $type, package: $package, parent: $parent, alias: $alias");

    // Check redis
    $this->check_redis_component($type, $alias, $package, $parent);

}

/**
 * Provider -- create components 
 */
public function provider_create()
{ 

    // Initialize
    $pkg = 'unit_test';

    // Set array
    $vars = array(
        array('ajax', $pkg . ':utest', '', array("src/$pkg/ajax/utest.php" => "class utest")), 
        array('autosuggest', $pkg . ':find_test', '', array("src/$pkg/autosuggest/find_test.php" => "class find_test")), 
        array('controller', $pkg . ':providers', $pkg, array()),
        array('controller', $pkg . ':providers:general', $pkg, array("src/$pkg/controller/providers/general.php" => "class general")), 
        array('cron', $pkg . ':utest', '', array("src/$pkg/cron/utest.php" => "class utest")), 
        array('form', $pkg . ':utest', '', array("src/$pkg/form/utest.php" => "class utest")), 
        array('htmlfunc', $pkg . ':utest', '', array("src/$pkg/htmlfunc/utest.php" => "class utest", "views/components/htmlfunc/$pkg/utest.tpl" => "")),
        array('lib', $pkg . ':mytest', '', array("src/$pkg/mytest.php" => "class mytest")),
        array('modal', $pkg . ':utest', '', array("src/$pkg/modal/utest.php" => "class utest", "views/components/modal/$pkg/utest.tpl" => '')),
        array('tabcontrol', $pkg . ':utest', '', array("src/$pkg/tabcontrol/utest.php" => "class utest")),
        array('tabpage', $pkg . ':utest:profile', $pkg, array("src/$pkg/tabcontrol/utest/profile.php" => "class profile", "views/components/tabpage/$pkg/utest/profile.tpl" => "")),
        array('table', $pkg . ':utest', '', array("src/$pkg/table/utest.php" => "class utest")), 
        array('view', "admin/testing/create", $pkg, array("views/php/admin/testing/create.php" => "namespace apex", "views/tpl/admin/testing/create.tpl" => "")),
        array('test', $pkg . ':misc', '', array("tests/$pkg/misc_test.php" => "class test_misc")),
        array('worker', $pkg . ':utest', 'users.profile', array("src/$pkg/worker/utest.php" => "class utest"))
    );

    // Return
    return $vars;

}

/**
 * publish 
 */
public function test_publish()
{ 

    // Ensure package does not exist in repo
    $repo_dir = SITE_PATH . '/storage/repo/public/' . $this->pkg_alias;
    if (is_dir($repo_dir)) { io::remove_dir($repo_dir); }
    db::query("DELETE FROM repo_packages WHERE type = 'package' AND alias = %s", $this->pkg_alias);
    db::query("DELETE FROM internal_upgrades WHERE package = %s", $this->pkg_alias);

    // Publish package
    $response = $this->send_cli('publish', array($this->pkg_alias));
    $this->assertStringContains($response, "Successfully published");

    // Check files
    $this->assertdirectoryexists($repo_dir);
    $this->assertfileexists("$repo_dir/latest.zip");
    $this->assertfileexists("$repo_dir/" . $this->pkg_alias . "-1_0_0.zip");

    // Check database
    $row = db::query("SELECT * FROM repo_packages WHERE alias = %s", $this->pkg_alias);
    $this->assertnotfalse($row, "Package not added to repo mySQL table upon publish");

}

/**
 * Verify publish package 
 */
public function test_publish_verify()
{ 

    // Unpack archive
    $tmp_dir = sys_get_temp_dir() . '/test';
    if (is_dir($tmp_dir)) { io::remove_dir($tmp_dir); }
    io::unpack_zip_archive(SITE_PATH . '/storage/repo/public/unit_test/latest.zip', $tmp_dir);

    // Verify unpacked directory
    $this->assertdirectoryexists($tmp_dir, "Invalid zip archive, unable to publish package.");
    $this->assertdirectoryexists("$tmp_dir/files", "Invalid zip archive, unable to publish package.");
    $this->assertdirectoryisreadable($tmp_dir, "Invalid zip archive, unable to publish package.");
    $this->assertdirectoryisreadable("$tmp_dir/files", "Invalid zip archive, unable to publish package.");

    // Verrify package files are there
    $files = array('package.php', 'toc.json', 'components.json');
    foreach ($files as $file) { 
        $this->assertfileexists("$tmp_dir/$file", "The file $file does not exist within zip archive, unable to publish");
        $this->assertfileisreadable("$tmp_dir/$file", "File is not readable $file within zip archive, unable to publish");
    }


    // Get JSON to verify against
    $verify_components = json_decode(base64_decode('W3sidHlwZSI6ImFqYXgiLCJvcmRlcl9udW0iOjAsInBhY2thZ2UiOiJ1bml0X3Rlc3QiLCJwYXJlbnQiOiIiLCJhbGlhcyI6InV0ZXN0IiwidmFsdWUiOiIifSx7InR5cGUiOiJhdXRvc3VnZ2VzdCIsIm9yZGVyX251bSI6MCwicGFja2FnZSI6InVuaXRfdGVzdCIsInBhcmVudCI6IiIsImFsaWFzIjoiZmluZF90ZXN0IiwidmFsdWUiOiIifSx7InR5cGUiOiJjb250cm9sbGVyIiwib3JkZXJfbnVtIjowLCJwYWNrYWdlIjoidW5pdF90ZXN0IiwicGFyZW50IjoiIiwiYWxpYXMiOiJwcm92aWRlcnMiLCJ2YWx1ZSI6IiJ9LHsidHlwZSI6ImNvbnRyb2xsZXIiLCJvcmRlcl9udW0iOjAsInBhY2thZ2UiOiJ1bml0X3Rlc3QiLCJwYXJlbnQiOiJwcm92aWRlcnMiLCJhbGlhcyI6ImdlbmVyYWwiLCJ2YWx1ZSI6IiJ9LHsidHlwZSI6ImNyb24iLCJvcmRlcl9udW0iOjAsInBhY2thZ2UiOiJ1bml0X3Rlc3QiLCJwYXJlbnQiOiIiLCJhbGlhcyI6InV0ZXN0IiwidmFsdWUiOiIifSx7InR5cGUiOiJmb3JtIiwib3JkZXJfbnVtIjowLCJwYWNrYWdlIjoidW5pdF90ZXN0IiwicGFyZW50IjoiIiwiYWxpYXMiOiJ1dGVzdCIsInZhbHVlIjoiIn0seyJ0eXBlIjoiaHRtbGZ1bmMiLCJvcmRlcl9udW0iOjAsInBhY2thZ2UiOiJ1bml0X3Rlc3QiLCJwYXJlbnQiOiIiLCJhbGlhcyI6InV0ZXN0IiwidmFsdWUiOiIifSx7InR5cGUiOiJsaWIiLCJvcmRlcl9udW0iOjAsInBhY2thZ2UiOiJ1bml0X3Rlc3QiLCJwYXJlbnQiOiIiLCJhbGlhcyI6Im15dGVzdCIsInZhbHVlIjoiIn0seyJ0eXBlIjoibW9kYWwiLCJvcmRlcl9udW0iOjAsInBhY2thZ2UiOiJ1bml0X3Rlc3QiLCJwYXJlbnQiOiIiLCJhbGlhcyI6InV0ZXN0IiwidmFsdWUiOiIifSx7InR5cGUiOiJ0YWJjb250cm9sIiwib3JkZXJfbnVtIjowLCJwYWNrYWdlIjoidW5pdF90ZXN0IiwicGFyZW50IjoiIiwiYWxpYXMiOiJ1dGVzdCIsInZhbHVlIjoiIn0seyJ0eXBlIjoidGFicGFnZSIsIm9yZGVyX251bSI6MCwicGFja2FnZSI6InVuaXRfdGVzdCIsInBhcmVudCI6InV0ZXN0IiwiYWxpYXMiOiJwcm9maWxlIiwidmFsdWUiOiIifSx7InR5cGUiOiJ0YWJsZSIsIm9yZGVyX251bSI6MCwicGFja2FnZSI6InVuaXRfdGVzdCIsInBhcmVudCI6IiIsImFsaWFzIjoidXRlc3QiLCJ2YWx1ZSI6IiJ9LHsidHlwZSI6InZpZXciLCJvcmRlcl9udW0iOjAsInBhY2thZ2UiOiJ1bml0X3Rlc3QiLCJwYXJlbnQiOiIiLCJhbGlhcyI6ImFkbWluXC90ZXN0aW5nXC9jcmVhdGUiLCJ2YWx1ZSI6IiJ9LHsidHlwZSI6InRlc3QiLCJvcmRlcl9udW0iOjAsInBhY2thZ2UiOiJ1bml0X3Rlc3QiLCJwYXJlbnQiOiIiLCJhbGlhcyI6Im1pc2MiLCJ2YWx1ZSI6IiJ9LHsidHlwZSI6IndvcmtlciIsIm9yZGVyX251bSI6MCwicGFja2FnZSI6InVuaXRfdGVzdCIsInBhcmVudCI6IiIsImFsaWFzIjoidXRlc3QiLCJ2YWx1ZSI6InVzZXJzLnByb2ZpbGUifV0='), true);
    $verify_toc = json_decode(base64_decode('eyJzcmNcL3VuaXRfdGVzdFwvYWpheFwvdXRlc3QucGhwIjoxLCJzcmNcL3VuaXRfdGVzdFwvYXV0b3N1Z2dlc3RcL2ZpbmRfdGVzdC5waHAiOjIsInNyY1wvdW5pdF90ZXN0XC9jb250cm9sbGVyXC9wcm92aWRlcnMucGhwIjozLCJzcmNcL3VuaXRfdGVzdFwvY29udHJvbGxlclwvcHJvdmlkZXJzXC9nZW5lcmFsLnBocCI6NCwic3JjXC91bml0X3Rlc3RcL2Nyb25cL3V0ZXN0LnBocCI6NSwic3JjXC91bml0X3Rlc3RcL2Zvcm1cL3V0ZXN0LnBocCI6Niwic3JjXC91bml0X3Rlc3RcL2h0bWxmdW5jXC91dGVzdC5waHAiOjcsInZpZXdzXC9jb21wb25lbnRzXC9odG1sZnVuY1wvdW5pdF90ZXN0XC91dGVzdC50cGwiOjgsInNyY1wvdW5pdF90ZXN0XC9teXRlc3QucGhwIjo5LCJzcmNcL3VuaXRfdGVzdFwvbW9kYWxcL3V0ZXN0LnBocCI6MTAsInZpZXdzXC9jb21wb25lbnRzXC9tb2RhbFwvdW5pdF90ZXN0XC91dGVzdC50cGwiOjExLCJzcmNcL3VuaXRfdGVzdFwvdGFiY29udHJvbFwvdXRlc3QucGhwIjoxMiwidmlld3NcL2NvbXBvbmVudHNcL3RhYnBhZ2VcL3VuaXRfdGVzdFwvdXRlc3RcL3Byb2ZpbGUudHBsIjoxNiwic3JjXC91bml0X3Rlc3RcL3RhYmNvbnRyb2xcL3V0ZXN0XC9wcm9maWxlLnBocCI6MTUsInNyY1wvdW5pdF90ZXN0XC90YWJsZVwvdXRlc3QucGhwIjoxNywidmlld3NcL3BocFwvYWRtaW5cL3Rlc3RpbmdcL2NyZWF0ZS5waHAiOjE4LCJ2aWV3c1wvdHBsXC9hZG1pblwvdGVzdGluZ1wvY3JlYXRlLnRwbCI6MTksInRlc3RzXC91bml0X3Rlc3RcL21pc2NfdGVzdC5waHAiOjIwLCJzcmNcL3VuaXRfdGVzdFwvd29ya2VyXC91dGVzdC5waHAiOjIxLCJzcmNcL3VuaXRfdGVzdC50eHQiOjIyLCJwdWJsaWNcL3VuaXRfdGVzdFwvdDIudHh0IjoyMywicHVibGljXC91bml0X3Rlc3RcL3QxLnR4dCI6MjQsImRvY3NcL3VuaXRfdGVzdFwvaW5kZXgubWQiOjI1LCJzcmNcL3VuaXRfdGVzdFwvdHBsXC9wdWJsaWNcL3Rlc3QxMjM0NS50cGwiOjI2fQ=='), true);

    // Get actual JSON
    $toc = json_decode(file_get_contents("$tmp_dir/toc.json"), true);
    $components = json_decode(file_get_contents("$tmp_dir/components.json"), true);

    // Compare TOC files
    foreach ($verify_toc as $file => $num) { 
        $this->assertcontains($file, array_keys($toc), "TOC does not contain the file, $file");
    }

    // Reverse verify TOC files
    foreach ($toc as $file => $num) { 
        $this->assertcontains($file, array_keys($verify_toc), "Extra file is in TOC, $file");
    }

    // GO through components
    $verify_comps = array();
    foreach ($verify_components as $row) { 
        $verify_comps[] = implode(":", array($row['type'], $row['parent'], $row['alias']));
    }

    // Go through actual comps
    $comps = array();
    foreach ($components as $row) { 
        $line = implode(":", array($row['type'], $row['parent'], $row['alias']));
        $this->assertcontains($line, $verify_comps, "Have an extra component, $line");
        $comps[] = $line;
    }

    // Reverse check the compons
    foreach ($verify_comps as $line) { 
        $this->assertcontains($line, $comps, "Missing component in JSON, $line");
    }

}

/**
 * Delete child components 
 */
public function test_delete_children()
{ 

    // Delete
    $this->delete_component('tabpage', $this->pkg_alias . ':utest:profile', $this->pkg_alias, array("src/$this->pkg_alias/tabcontrol/utest/profile.php" => "class profile", "views/components/tabpage/$this->pkg_alias/utest/profile.tpl" => ""));
    $this->delete_component('controller', $this->pkg_alias . ':providers:general', '', array("src/$this->pkg_alias/controller/providers/general.php" => ""));
    $this->asserttrue(true);

}

/**
 * delete components 
 *     @dataProvider provider_create 
 */
public function test_delete($type, $comp_alias, $owner, $files)
{ 

    // Skip, if needed
    if ($type == 'tabpage' || ($type == 'controller' && $comp_alias = $this->pkg_alias . ':providers:general')) { 
        $this->asserttrue(true);
        return;
    }

    // Delete
    $this->delete_component($type, $comp_alias, $owner, $files);

}

/**
 * Delete an individual component 
 */
private function delete_component($type, $comp_alias, $owner, $files)
{ 

    // Check component
    if (!list($alias, $package, $parent) = components::check($type, $comp_alias)) { 
        $this->asserttrue(false, "Unable to delete component, as it does not exist!  Type: $type, comp alias: $comp_alias");
    } else { $this->asserttrue(true); }

    // Delete
    $response = $this->send_cli('delete', array($type, $comp_alias));
    $this->assertStringContains($response, "Successfully deleted");

    // Check files
    foreach ($files as $file => $text) { 
        $this->assertfilenotexists(SITE_PATH . '/' . $file, "Deleting component did not delete the file, $file");
    }

    // Check mySQL row
    $row = db::get_row("SELECT * FROM internal_components WHERE type = %s AND package = %s AND parent = %s AND alias = %s", $type, $package, $parent, $alias);
    $this->assertfalse($row, "DId not delete component from mySQL, type: $type, package: $package, parent: $parent, alias: $alias");

    // Check redis
    $chk = implode(":", array($type, $package, $parent, $alias));
    $ok = redis::sismember('config:components', $chk);
    $this->assertfalse($ok, "Did not delete components from redis, type: $type, package: $package, parent: $parent, alias: $alias");

}

/**
 * Test reset package
 */
public function test_package_reset()
{

    $client = app::make(package::class);
    $client->reset($this->pkg_alias);
    $this->assertTrue(true);

}

/**
 * Re-install components
 */
public function test_reinstall_components()
{

    $client = app::make(package::class);
    //$client->reinstall_components($this->pkg_alias);
    $this->assertTrue(true);

}

/**
 * delete-package 
 */
public function test_delete_package()
{ 

    // Send request
    $response = $this->send_cli('delete_package', array($this->pkg_alias));
    $this->assertStringContains($response, "Successfully deleted the package", "Unable to delete package, $this->pkg_alias");

// Check directories
    $dirs = array(
        SITE_PATH . "/etc/$this->pkg_alias",
        SITE_PATH . "/etc/$this->pkg_alias/upgrades",
        SITE_PATH . "/src/$this->pkg_alias"
    );
    foreach ($dirs as $dir) { 
        $this->assertdirectorynotexists($dir, "Deleteing package did not remove directories");
    }

    // Check for package.php file
    $this->assertfilenotexists(SITE_PATH . "/etc/$this->pkg_alias/package.php", "Deleting the packge did not remove the package.php file");

    // Check database row
    $row = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", $this->pkg_alias);
    $this->assertfalse($row, "Deleting package did not remove database row");

}

/**
 * install a package
 */
public function test_install()
{ 

    // Send request
    $response = $this->send_cli('install', array($this->pkg_alias));
    $this->assertStringContains($response, "Successfully installed");

    // Check directories
    $dirs = array(
        SITE_PATH . "/etc/$this->pkg_alias",
        SITE_PATH . "/src/$this->pkg_alias"
    );
    foreach ($dirs as $dir) { 
        $this->assertdirectoryexists($dir, "Creating package did not create necessary directories");
        $this->assertdirectoryiswritable($dir, "Creating package did not create writeable directories");
    }

    // Check files
    $files = array();
    //$files = array('package.php', 'install.sql', 'remove.sql', 'reset.sql');
    foreach ($files as $file) { 
        $this->assertfileexists(SITE_PATH . "/etc/$this->pkg_alias/$file", "Creating package did not create the /etc/$file file");
        $this->assertfileiswritable(SITE_PATH . "/etc/$this->pkg_alias/$file", "Package creation did not create writeable file at /etc/$file");
    }
    $this->assertFileContains(SITE_PATH . '/etc/unit_test/package.php', "class pkg_unit_test");

    // Check database row
    $row = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", $this->pkg_alias);
    $this->assertnotfalse($row, "Package creation did not add row into database with alias $this->pkg_alias");
    $this->assertisarray($row, "Creating package did not return array for database row");

// Check database row values
    $chk_vars = array(
        'access' => 'public', 
        'version' => '1.0.0',
        'prev_version' => '0.0.0',
        'alias' => $this->pkg_alias,
        'name' => $this->pkg_alias
    );
    foreach ($chk_vars as $key => $value) { 
        $this->assertarrayhaskey($key, $row, "Creating package database row does not have column $key");
        $this->assertequals($value, $row[$key], "Creating package database row column $key does not equal $value");
    }

}

/**
 * Verify installed components @dataProvider provider_create 
 */
public function test_install_verify_components($type, $comp_alias, $owner, $files)
{ 

    // Set variables
    $pkg = $this->pkg_alias;

    $this->asserttrue(true);
    $this->verify_component_created($type, $comp_alias, $owner, $files);

}

/**
 * Upgrade - create - invalid version
 */
public function test_upgrade_create_invalid_version()
{
    $this->waitException('version is invalid');
    $client = app::make(upgrade::class);
    $client->create('unit_test', 'junk');

}

/**
 * create_upgrade 
 */
public function test_create_upgrade()
{ 

    // SEnd request
    $response = $this->send_cli('create_upgrade', array($this->pkg_alias));
    $this->assertStringContains($response, "Successfully created upgrade point");

    // Verify upgrade point
    $upgrade_dir = SITE_PATH . '/etc/' . $this->pkg_alias . '/upgrades/1.0.1';
    $this->assertdirectoryexists($upgrade_dir);
    $this->assertdirectoryiswritable($upgrade_dir);

    // Check upgrade files
    $files = array('upgrade.php', 'install.sql', 'rollback.sql', 'components.json');
    foreach ($files as $file) { 
        $this->assertfileexists("$upgrade_dir/$file");
        $this->assertfileiswritable("$upgrade_dir/$file");
    }

    // Check for database row
    $row = db::get_row("SELECT * FROM internal_upgrades WHERE package = %s AND version = '1.0.1' AND status = 'open'", $this->pkg_alias);
    $this->assertnotfalse($row, "Upgrade point does not exist in mySQL database");

    // Create some components
    $this->send_cli('create', array('lib', $this->pkg_alias . ':upgrade', ''));
    $this->send_cli('create', array('table', $this->pkg_alias . ':upgrade', ''));
    $this->send_cli('delete', array('form', $this->pkg_alias . ':utest'));
    file_put_contents(SITE_PATH . '/src/unit_test/mytest.php', "\n\nUpdating Test\n\n");

    // Confirm components created / deleted
    $this->verify_component_created('lib', $this->pkg_alias . ':upgrade', '', array("src/$this->pkg_alias/upgrade.php" => "class upgrade"));
    $this->verify_component_created('table', $this->pkg_alias . ':upgrade', '', array("src/$this->pkg_alias/table/upgrade.php" => "class upgrade"));
    $this->assertfilenotexists(SITE_PATH . '/src/unit_test/form/utest.php');

}

/**
 * publish_upgrade 
 */
public function test_publish_upgrade()
{ 

    // Update docs
    io::create_dir(SITE_PATH . '/docs/unit_test');
    file_put_contents(SITE_PATH . '/docs/unit_test/index.md', "<h1>Update Test</h1>\n\n");

    // Send request
    $response = $this->send_cli('publish_upgrade', array($this->pkg_alias, '', '1'));
    $this->assertStringContains($response, "Successfully published the appropriate upgrade");

    // Check database row
    $pkghash = 'package:' . $this->pkg_alias;
    $row = db::get_row("SELECT * FROM repo_upgrades WHERE pkghash = %s AND version = '1.0.1'", $pkghash);
    $this->assertnotfalse($row, "Upgrade is not in the repository mySQL!");

    // Revert package to before upgrade
    $this->send_cli('delete', array('lib', $this->pkg_alias . ':upgrade', ''));
    $this->send_cli('delete', array('table', $this->pkg_alias . ':upgrade', ''));
    $this->send_cli('create', array('form', $this->pkg_alias . ':utest'));
    file_put_contents(SITE_PATH . '/src/unit_test/mytest.php', "\n\nREVERT\n\n");

    // Get exception -- no open upgrades
    db::query("DELETE FROM internal_upgrades WHERE package = %s AND status = 'open'", $this->pkg_alias);
    $this->waitException('There are no open upgrades');
    $this->send_cli('publish_upgrade', array($this->pkg_alias));

}

/**
 * Check for upgrades 
 */
public function test_check_upgrades()
{ 

    // Update database
    db::query("UPDATE internal_packages SET version = '1.0.0' WHERE alias = %s", $this->pkg_alias);

    // Send request
    $response = $this->send_cli('check_upgrades');
    $this->assertStringContains($response, $this->pkg_alias);
    $this->assertStringContains($response, '1.0.1');

}

/**
 * upgrade 
 */
public function test_upgrade()
{ 

    // Send request
    $response = $this->send_cli('upgrade', array($this->pkg_alias));
    $this->assertStringContains($response, "Successfully upgraded the packages");

    // Confirm components created / deleted
    $this->verify_component_created('lib', $this->pkg_alias . ':upgrade', '', array("src/$this->pkg_alias/upgrade.php" => "class upgrade"));
    $this->verify_component_created('table', $this->pkg_alias . ':upgrade', '', array("src/$this->pkg_alias/table/upgrade.php" => "class upgrade"));
    $this->assertfilenotexists(SITE_PATH . '/src/unit_test/form/utest.php');
    $this->assertFileContains(SITE_PATH . '/src/unit_test/mytest.php', "Updating Test");

    // Check package row
    $row = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", $this->pkg_alias);
    $this->assertnotfalse($row);
    $this->assertisarray($row);
    $this->assertequals('1.0.1', $row['version']);

}

/**
 * Rollback
 */
public function test_rollback()
{

    //$response = $this->send_cli('rollback', array($this->pkg_alias, '1.0.0'));
    //$this->assertStringContains($response, 'Successfully');
    $client = app::make(upgrade::class);
    $client->rollback($this->pkg_alias, '1.0.0');
    $this->assertTrue(true);

}

/**
 * Check upgrades - none exist
 */
public function test_check_upgrades_not_exists()
{

    $response = $this->send_cli('check_upgrades');
    $this->assertStringContains($response, 'upgrades');


}

/**
 * delete-package2 
 */
public function test_delete_package2()
{ 

    // Send request
    $response = $this->send_cli('delete_package', array($this->pkg_alias));
    $this->assertStringContains($response, "Successfully deleted the package", "Unable to delete package, $this->pkg_alias");

// Check directories
    $dirs = array(
        SITE_PATH . "/etc/$this->pkg_alias",
        SITE_PATH . "/etc/$this->pkg_alias/upgrades",
        SITE_PATH . "/src/$this->pkg_alias", 
        SITE_PATH . "/tests/$this->pkg_alias"
    );
    foreach ($dirs as $dir) { 
        $this->assertdirectorynotexists($dir, "Deleteing package did not remove directories");
    }

    // Check for package.php file
    $this->assertfilenotexists(SITE_PATH . "/etc/$this->pkg_alias/package.php", "Deleting the packge did not remove the package.php file");
    $this->assertfilenotexists(SITE_PATH . "/src/unit_test.txt", "Deleting the packge did not remove the src/unit_test.txt file");

    // Check database row
    $row = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", $this->pkg_alias);
    $this->assertfalse($row, "Deleting package did not remove database row");

}

/**
 * create_theme 
 */
public function test_create_theme()
{ 

    // Ensure theme doesn't exist
    db::query("DELETE FROM internal_themes WHERE alias = 'utest'");
    io::remove_dir(SITE_PATH . '/views/themes/utest');
    io::remove_dir(SITE_PATH . '/public/themes/utest');

    // Send request
    $response = $this->send_cli('create_theme', array('utest', 'public', $this->repo_id));
    $this->assertStringContains($response, "Successfully created new theme");

    // Check directories
    $theme_dir = SITE_PATH . '/views/themes/utest';
    $dirs = array(
        $theme_dir,
        "$theme_dir/sections",
        "$theme_dir/layouts",
        SITE_PATH . '/public/themes/utest'
    );
    foreach ($dirs as $dir) { 
        $this->assertdirectoryexists($dir);
        $this->assertdirectoryiswritable($dir);
    }

    // Theme.php file
    $this->assertfileexists("$theme_dir/theme.php");
    $this->assertfileiswritable("$theme_dir/theme.php");

    // Check database row
    $row = db::get_row("SELECT * FROM internal_themes WHERE alias = 'utest'");
    $this->assertnotfalse($row);
    $this->assertisarray($row);

    // Set theme
    $old_theme = app::get_theme();
    app::set_theme('utest');
    $this->assertEquals('utest', app::get_theme());
    app::set_theme($old_theme);

    // Set invalid theme, triger exception
    $this->waitException('Invalid theme specified');
    app::set_theme('junk23423523532');

}

/**
 * publish_theme 
 */
public function test_publish_theme()
{ 

    // Make sure theme isn't in repo
    io::remove_dir(SITE_PATH . '/storage/repo/public_themes/utest');
    db::query("DELETE FROM repo_packages WHERE type = 'theme' AND alias = 'utest'");

    // Save some files
    if (!is_dir(SITE_PATH . '/public/themes/utest/css')) { 
        mkdir(SITE_PATH . '/public/themes/utest/css');
    }
    file_put_contents(SITE_PATH . '/public/themes/utest/css/styles.css', "COME CSS STYLES GO HERE\n\n");
    file_put_contents(SITE_PATH . '/views/themes/utest/sections/header.tpl', "The theme header");
    file_put_contents(SITE_PATH . '/views/themes/utest/sections/footer.tpl', "And the footer");

    // Save /tpl/ directory and file
    io::create_dir(SITE_PATH . '/views/themes/utest/tpl');
    file_put_contents(SITE_PATH . '/views/themes/utest/tpl/utest123.tpl', 'unit test');


    // Send request
    $response = $this->send_cli('publish_theme', array('utest'));
    $this->assertStringContains($response, "Successfully published the theme");

    // Check files
    $this->assertfileexists(SITE_PATH . '/storage/repo/public_themes/utest/latest.zip');

    // Check row
    $row = db::get_row("SELECT * FROM repo_packages WHERE type = 'theme' AND alias = 'utest'");
    $this->assertnotfalse($row);
    $this->assertisarray($row);

}

/**
 * Delete theme 
 */
public function test_delete_theme()
{ 

    // Send request
    $response = $this->send_cli('delete_theme', array('utest'));
    $this->assertStringContains($response, "Successfully deleted the theme");

    // Check directories are gone
    $this->assertdirectorynotexists(SITE_PATH . '/views/themes/utest');
    $this->assertdirectorynotexists(SITE_PATH . '/public/themes/utest');

    // Check mySQL database
    $row = db::get_row("SELECT * FROM internal_themes WHERE alias = 'utest'");
    $this->assertfalse($row);

}

/**
 * list_themes 
 */
public function test_list_themes()
{ 

    // Send request
    $response = $this->send_cli('list_themes', array());
    $this->assertStringContains($response, 'utest');

}

/**
 * install_theme 
 */
public function test_install_theme()
{ 

    // Send request
    $response = $this->send_cli('install_theme', array('utest'));
    $this->assertStringContains($response, "Successfully downloaded and installed");

    // Verify
    $this->assertdirectoryexists(SITE_PATH . '/views/themes/utest');
    $this->assertdirectoryexists(SITE_PATH . '/views/themes/utest/sections');
    $this->assertdirectoryexists(SITE_PATH . '/public/themes/utest');
    $this->assertdirectoryexists(SITE_PATH . '/public/themes/utest/css');

    // Check files
    $this->assertFileContains(SITE_PATH . '/public/themes/utest/css/styles.css', "OME CSS STYLES");
    $this->assertFileContains(SITE_PATH . '/views/themes/utest/sections/header.tpl', "The theme header");
    $this->assertFileContains(SITE_PATH . '/views/themes/utest/sections/footer.tpl', "And the footer");

}

/**
 * change_theme 
 */
public function test_change_theme()
{ 

    // Get old theme
    $old_theme = app::_config('core:theme_public');

    // Send
    $response = $this->send_cli('change_theme', array('public', 'utest'));
    $this->assertStringContains($response, "Successfully changed the theme");
    $this->assertequals('utest', app::_config('core:theme_public'));
    app::update_config_var('core:theme_public', $old_theme);

    // Update members
    app::change_theme('members', app::_config('users:theme_members'));
    app::change_theme('public', 'koupon');

    // Delete theme
    $this->send_cli('delete_theme', array('utest'));

    // Trigger exception
    $this->waitException('Invalid theme specified');
    app::change_theme('public', 'junk891252152');

}

/**
 * mode 
 */
public function test_mode()
{ 

    // Send request
    $response = $this->send_cli('mode', array('prod', '0'));
    $this->assertStringContains($response, "Successfully updated server mode");

    // Check redis
    $this->assertequals('prod', app::_config('core:mode'), "Did not change the server mode to 'rpdo'");
    $this->assertequals(0, app::_config('core:debug_level'), "Did not update the debug level to 0");

    // Change back
    $this->send_cli('mode', array('devel', '3'));
    $this->assertStringContains($response, "Successfully updated server mode");

}

/**
 * debug 
 */
public function test_debug()
{ 

    // Send
    $response = $this->send_cli('debug', array('2'));
    $this->assertStringContains($response, "Successfully changed debugging mode");
    $this->assertequals(2, app::_config('core:debug'));

    // Change agian
    $response = $this->send_cli('debug', array('0'));
    $this->assertStringContains($response, "Successfully changed debugging mode");

}

/**
 * server_typpe 
 */
public function test_server_type()
{ 

    // Update
    $response = $this->send_cli('server_type', array('dbm'));
    $this->assertStringContains($response, "Successfully updated server type");
    $this->assertequals('dbm', app::_config('core:server_type'));

    // Revert
    $response = $this->send_cli('server_type', array('all'));
    $this->assertStringContains($response, "Successfully updated server type");

}

/**
 * scan - no package
 */
public function test_scan_no_package()
{

    // Check non-existent package
    $response = $this->send_cli('scan', array('some_junk_package_that_will_never_exist'));
    $this->assertStringContains($response, 'does not exist');

    // Check for exception
    $this->waitException('You did not specify a package alias');
    $this->send_cli('scan');

}

/**
 * create_package - package already exists
 */
public function test_create_package_already_exists()
{

    $this->waitException('The package already exists');
    $this->send_cli('create_package', array('users'));

}

/**
 * create_package - invalid alias
 */
public function test_create_package_invalid_alias()
{

    $this->waitException('An invalid package alias');
    $this->send_cli('create_package', array('Gj!g@gjd#'));

}

/**
 * delete_package --- not exists
 */
public function test_delete_package_not_exists()
{

    $this->waitException('The package does not exist');
    $this->send_cli('delete_package', array('some_junk_package_that_will_never_exist'));

}

/**
 * publish - undefined
 */
public function test_publish_undefined()
{

    // Package already exists
    $response = $this->send_cli('publish', array('some_junk_package_that_will_never_exist'));
    $this->assertStringContains($response, 'Package does not exist');

    // Undefined
    $this->waitException('You did not specify');
    $this->send_cli('publish');

}

/**
 * create_upgrade - undefined
 */
public function test_create_upgrade_undefined()
{

    // Wait exception
    $this->waitException('You did not specify');
    $this->send_cli('create_upgrade');

}

/**
 * publish_upgrade - undefined
 */
public function test_publish_upgrade_undefined()
{

    // Wait for exception
    $this->waitException('You did not specify');
    $this->send_cli('publish_upgrade');

}

/**
 * publish_upgrade - not exists
 */
public function test_publish_upgrade_not_exists()
{

    // Wait exception
    $this->waitException('The package does not exist');
    $this->send_cli('publish_upgrade', array('some_package_that_will_never_exist'));

}

/**
 * change_theme - invalid area
 */
public function test_change_area_invalid_area()
{

    $this->waitException('Invalid area specified');
    $this->send_cli('change_theme', array('dsagsdgas', 'test'));

}

/** 
 * change_theme - not exists
 */
public function test_change_theme_not_exists()
{

    $this->waitException('No theme exists');
    $this->send_cli('change_theme', array('public', 'some_theem_that_will_never_exist'));

}

/**
 * create - invalid component type
 */
public function test_create_invalid_type()
{

    $this->waitException('Component type is invalid');
    $this->send_cli('create', array('dsgadsgsa'));

}

/**
 * create - invalid component alias
 */
public function test_create_invalid_alias()
{

    $this->waitException('Invalid component alias');
    $this->send_cli('create', array('lib', 'sdgsdagdsa'));

}

/**
 * delete - not exists
 */
public function test_delete_not_exists()
{

    $this->waitException('does not exist');
    $this->send_cli('delete', array('lib', 'sdgsadgdsa'));

}

/**
 * mode - invalid
 */
public function test_mode_invalid()
{

    $this->waitException('You must specify the mode');
    $this->send_cli('mode', array('dsgsdgds'));

}

/**
 * server_type -- invalid
 */
public function test_server_type_invalid()
{

    $this->waitException('Invalid server type');
    $this->send_cli('server_type', array('sdfsdgs'));

}

/**
 * add_repo - not exists
 */
public function test_add_repo_not_exists()
{

    $this->waitException('No repository exists');
    $this->send_cli('add_repo');

}

/**
 * add_repo - invalid repo
 */
public function test_add_repo_invalid_url()
{

    $this->waitException('No valid repository');
    $this->send_cli('add_repo', array('google.com'));

}

/**
 * Update repo - not exists
 */
public function test_update_repo_not_exists()
{

    $this->waitException('No repository exists');
    $this->send_cli('update_repo', array('sdfsdgds'));

}

/**
 * Clear db slaves
 */
public function test_clear_dbslaves()
{

    $response = $this->send_cli('clear_dbslaves');
    $this->assertStringContains($response, 'Successfully cleared all database slave');

}

/**
 * Compile core
 */
public function test_compile_core()
{

    $response = $this->send_cli('compile_core');
    $this->assertStringContains($response, 'Successfully compiled the core');

}

/**
 * Enable cache
 */
public function test_enable_cache()
{

    $response = $this->send_cli('enable_cache');
    $this->assertStringContains($response, 'Successfully enabled the cache');
    $this->assertEquals(1, (int) app::_config('core:cache'));

}

/**
 * Disable cache
 */
public function test_disable_cache()
{

    $response = $this->send_cli('disable_cache');
    $this->assertStringContains($response, 'Successfully disabled the cache');
    $this->assertEquals(0, (int) app::_config('core:cache'));

}

}


