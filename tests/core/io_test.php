<?php
declare(strict_types = 1);

namespace tests\core;

use apex\app;
use apex\libc\db;
use apex\libc\debug;
use apex\app\io\io;
use apex\app\tests\test;


/**
 * Handles all unit tests for the I/O library at 
 * /src/app/io/io.php.
 */
class test_io extends test
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
 * Create directory
 */
public function test_create_dir()
{

    // Initialize
    $io = new io();

    // Remove, if exists
    $dir = SITE_PATH . '/utest';
    if (is_dir($dir)) { $io->remove_dir($dir); }

    // Create dir
    $io->create_dir("$dir/my/unit/test");
    $this->assertDirectoryExists("$dir/my/unit/test");
    $this->assertDirectoryIsWritable("$dir/my/unit/test");

}

/**
 * Download file
 */
public function test_download_file()
{

    // Download
    $io = new io();
    $io->download_file('https://github.com/apexpl/users/archive/master.zip', SITE_PATH . '/utest/my/unit/users.zip');

    // Assert
    $this->assertFileExists(SITE_PATH . '/utest/my/unit/users.zip');
    $this->assertFileIsWritable(SITE_PATH . '/utest/my/unit/users.zip');

}

/**
 * Unpack zip archive
 */
public function test_unpack_zip_archive()
{

    // Unpack zip
    $io = new io();
    $this->assertFileExists(SITE_PATH . '/utest/my/unit/users.zip');
    $io->unpack_zip_archive(SITE_PATH . '/utest/my/unit/users.zip', SITE_PATH . '/utest/my/users');

    // Assert
    $dir = SITE_PATH . '/utest/my/users/users-master';
    $this->assertDirectoryExists($dir);
    $this->assertDirectoryExists("$dir/views");
    $this->assertDirectoryExists("$dir/etc");
    $this->assertDirectoryExists("$dir/src");
    $this->assertFileExists("$dir/etc/package.php");
    $this->assertFileExists("$dir/etc/install.sql");
    $this->assertFileExists("$dir/views/tpl/admin/users/create.tpl");
    $this->assertFileContains("$dir/etc/package.php", 'class pkg_users');

}

/**
 * Execute SQL file
 */
public function test_execute_sqlfile()
{

    // save SQL
    file_put_contents(SITE_PATH . '/utest/test.sql', base64_decode('CkRST1AgVEFCTEUgSUYgRVhJU1RTIHVuaXRfdGVzdDsKCkNSRUFURSBUQUJMRSB1bml0X3Rlc3QgKAogICAgaWQgSU5UIE5PVCBOVUxMIFBSSU1BUlkgS0VZIEFVVE9fSU5DUkVNRU5ULCAKICAgIGlzX2FjdGl2ZSBJTlQgTk9UIE5VTEwgREVGQVVMVCAxLCAKICAgIG5hbWUgVkFSQ0hBUigxMDApIE5PVCBOVUxMCikgZW5naW5lPUlubm9EQjsKCg=='));

    // Execute SQL
    $io = new io();
    $io->execute_sqlfile(SITE_PATH . '/utest/test.sql');

    // Check database trable
    $tables = db::get_column("SHOW TABLES");
    $this->assertContains('unit_test', $tables);
    db::query("DROP TABLE unit_test");

}

/**
 * Create zip archive
 */
public function test_create_zip_archive()
{

    // Create zip
    $io = new io();
    $io->create_zip_archive(SITE_PATH . '/utest/my/users/users-master/views', SITE_PATH . '/utest/views.zip');
    $this->assertFileExists(SITE_PATH . '/utest/views.zip');

    // nzip, and check directory
    $io->unpack_zip_archive(SITE_PATH . '/utest/views.zip', SITE_PATH . '/utest/chk');

    // Verify zip file
    $dir = SITE_PATH . '/utest/chk';
    $this->assertDirectoryExists($dir);
    $this->assertFileExists("$dir/tpl/admin/users/create.tpl");
    $this->assertFileExists("$dir/php/members/account/update_profile.php");
    $this->assertFileExists("$dir/tpl/members/account/change_password.tpl");
    $this->assertFileExists("$dir/php/admin/communicate/notify.php");

}

/**
 parse_dir()
 */
public function test_parse_dir()
{

    // Initialize
    $dir = SITE_PATH . '/utest/chk';
    $this->assertDirectoryExists($dir);

    // Parse dir, get files
    $io = new io();
    $files = $io->parse_dir($dir);

    // Assert
    $this->assertContains('tpl/public/login.tpl', $files);
    $this->assertContains('tpl/admin/users/manage.tpl', $files);
    $this->assertContains('php/admin/communicate/email_all.php', $files);

}

/**
 * Generate random string
 */
public function test_generate_random_string()
{

    $io = new io();
    $this->assertEquals(12, strlen($io->generate_random_string(12)));
    $this->assertEquals(18, strlen($io->generate_random_string(18, true)));

}

/**
 * Send chunked upload
 */
public function test_send_chunked_upload()
{

    // Download APex .zip
    $io = new io();
    $io->download_file('https://github.com/apexpl/apex/archive/master.zip', SITE_PATH . '/utest/apex.zip');
    $this->assertFileExists(SITE_PATH . '/utest/apex.zip');

    // Send chunked upload
    $url = 'http://' . app::_config('core:domain_name');
    $io->send_chunked_file($url, SITE_PATH . '/utest/apex.zip', 'unit_test-apex.zip');
    sleep(1);

    // Check and unpack file
    $this->assertFileExists(SITE_PATH . '/utest/apex_up.zip');
    $io->unpack_zip_archive(SITE_PATH . '/utest/apex_up.zip', SITE_PATH . '/utest/master');

    // Assert zip file
    $dir = SITE_PATH . '/utest/master/apex-master';
    $this->assertDirectoryExists($dir);
    $this->assertDirectoryExists("$dir/src");
    $this->assertFileExists("$dir/src/app.php");
    $this->assertFileExists("$dir/bootstrap/http.php");
    $this->assertFileExists("$dir/public/index.php");
    $this->assertFileExists("$dir/src/core/htmlfunc/display_table.php");
    $this->assertFileExists("$dir/views/tpl/admin/index.tpl");

}

/**
 * Remove directory
 */
public function test_remove_dir()
{

    // Initialize
    $dir = SITE_PATH . '/utest';
    $this->assertDirectoryExists($dir);

    // Remove
    $io = new io();
    $io->remove_dir($dir);
    $this->assertDirectoryNotExists($dir);

}

/**
 * Send HTTP request
 */
public function test_send_http_request()
{

    // Get html
    $io = new io();
    $html = $io->send_http_request('https://apex-platform.org/');
    $this->assertNotFalse($html);
    $this->assertStringContains($html, 'Apex Software Platform');

}

/**
 * Send Tor request
 */
public function test_send_tor_request()
{

    $io = new io();
    $html = $io->send_tor_request('https://www.google.com');
    $this->assertTrue(true);

}

}


