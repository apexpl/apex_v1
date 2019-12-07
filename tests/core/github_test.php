<?php
declare(strict_types = 1);

namespace tests\core;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\svc\io;
use apex\app\pkg\github;
use apex\app\tests\test;


/**
 * Handles all unit tests for the git /Github integration functionality, which 
 * is mainly located at /src/app/pkg/github.com.
 */
class test_github extends test
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
    $this->pkg_alias = 'git_test';

}

/**
 * tearDown
 */
public function tearDown():void
{

}

/**
 * Install package
 */
public function test_install_package()
{

// Delete package, if needed
    if (check_package($this->pkg_alias) === true) { 
        $response = $this->send_cli('delete_package', array($this->pkg_alias));
        $this->assertStringContains($response, "Successfully deleted");
    }

    // Install package
    $response = $this->send_cli('install', array($this->pkg_alias));
    $this->assertStringContains($response, 'Successfully installed');
    $this->assertDirectoryExists(SITE_PATH . '/src/' . $this->pkg_alias);

}

/**
 * Initialize git repo
 */
public function test_init_repo()
{

    // Add library component
    $comp_alias = $this->pkg_alias . ':' . 'another_lib';
    $response = $this->send_cli('create', array('lib', $comp_alias));
    $this->assertStringContains($response, 'Successfully created');
    $this->assertFileExists(SITE_PATH . '/src/' . $this->pkg_alias . '/another_lib.php');

    // Init git repo
    $response = $this->send_cli('git_init', array($this->pkg_alias));
    $this->assertStringContains($response, 'Successfully initialized');
    $this->assertFileExists(SITE_PATH . '/src/' . $this->pkg_alias . '/git/git.sh');

    // Assert git.sh file
    $lines = file(SITE_PATH . '/src/' . $this->pkg_alias . '/git/git.sh');
    $this->assertEquals('git init', trim($lines[1]));
    $this->assertEquals('git remote add origin https://github.com/apexpl/git_test.git', trim($lines[2]));
    $this->assertEquals('git add src/another_lib.php', trim($lines[4]));
    $this->assertEquals('git push -u origin master', trim($lines[6]));

}

/**
 * Upgrade
 */
public function test_upgrade()
{

    // Delete component, if needed
    if (file_exists(SITE_PATH . '/src/' . $this->pkg_alias . '/upgrade.php')) { 
        $this->send_cli('delete', array('lib', $this->pkg_alias . ':upgrade'));
    }
    // Create upgrade point
    $response = $this->send_cli('create_upgrade', array($this->pkg_alias));
    $this->assertStringContains($response, 'Successfully created upgrade');

    // Create couple components
    $response = $this->send_cli('create', array('lib', $this->pkg_alias . ':upgrade'));
    $this->assertStringContains($response, 'Successfully created');
    $this->assertFileExists(SITE_PATH . '/src/' . $this->pkg_alias . '/upgrade.php');

    // Save modified upgrade.php file
    $code = base64_decode('PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XGdpdF90ZXN0OwoKdXNlIGFwZXhcYXBwOwp1c2UgYXBleFxzdmNcZGI7CnVzZSBhcGV4XHN2Y1xkZWJ1ZzsKCi8qKgogKiBCbGFuayBsaWJyYXJ5IGZpbGUgd2hlcmUgeW91IGNhbiBhZGQgCiAqIGFueSBhbmQgYWxsIG1ldGhvZHMgLyBwcm9wZXJ0aWVzIHlvdSBkZXNpcmUuCiAqLwpjbGFzcyB1cGdyYWRlCnsKCiAgICBwcml2YXRlICR0ZXh0ID0gJ35yYW5kb21fc3RyaW5nfic7CgoKfQoKCg==');
    $code = str_replace('~random_string~', io::generate_random_string(24), $code);
    file_put_contents(SITE_PATH . '/src/' . $this->pkg_alias . '/upgrade.php', $code);

    // Publish upgrade
    $response = $this->send_cli('publish_upgrade', array($this->pkg_alias, 1));
    $this->assertStringContains($response, 'publish to the git repository');
    $lines = file(SITE_PATH . '/src/' . $this->pkg_alias . '/git/git.sh');
    $this->assertEquals('git add src/upgrade.php', trim($lines[2]));

}

/**
 * Sync
 */
public function test_sync()
{

    // Delete library
    $response = $this->send_cli('delete', array('lib', $this->pkg_alias . ':somelib'));
    $this->assertStringContains($response, 'Successfully deleted');
    $this->assertFileNotExists(SITE_PATH . '/src/' . $this->pkg_alias . '/somelib.php');

    // Sync
    $response = $this->send_cli('git_sync', array($this->pkg_alias));
    $this->assertStringContains($response, 'Successfully synced');
    $this->assertFileExists(SITE_PATH . '/src/' . $this->pkg_alias . '/somelib.php');

}

}


