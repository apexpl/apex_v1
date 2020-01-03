<?php
declare(strict_types = 1);

namespace tests\core;

use apex\app;
use apex\libc\db;
use apex\libc\debug;
use apex\libc\io;
use apex\app\io\storage;
use apex\app\tests\test;


/**
 * Handles all unit tests for the remote file 
 * storage library located at /src/app/io/storage.php
 */
class test_storage extends test
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
 * Add file
 */
public function test_add()
{

    // Delete, if exists
    $file = SITE_PATH . '/storage/composer.json';
    if (file_exists($file)) { 
        @unlink($file);
    }

    // Add file
    $client = new storage();
    $client->add('composer.json', SITE_PATH . '/composer.json');
    $this->assertFileExists($file);
    $this->assertFileIsWritable($file);
    $this->assertFileContains($file, 'apex-platform.org');

    // Overwrite file
    $client->add('composer.json', SITE_PATH . '/Readme.md');
    $this->assertFileExists($file);
    $this->assertFileIsWritable($file);
    $this->assertFileContains($file, 'address');

    // Get exception
    $this->waitException('Unable to add file');
    $client->add('composer.json', SITE_PATH . '/apex', 'public', false);

}

/**
 * Add file - not exists
 */
public function test_add_not_exists()
{

    // Get exception
    $client = new storage();
    $this->waitException('Unable to add file');
    $client->add('test.txt', SITE_PATH  . '/some_junk_file_that_will_never_exist');

}

/**
 * Add large file
 */
public function test_add_large_file()
{

    // Delete, if needed
    if (file_exists(SITE_PATH . '/storage/apex.zip')) { 
        @unlink(SITE_PATH . '/storage/apex.zip');
    }

    // Download file
    $tmp_file = sys_get_temp_dir() . '/apex.zip';
    if (file_exists($tmp_file)) { @unlink($tmp_file); }
    io::download_file('https://github.com/apexpl/apex/archive/master.zip', $tmp_file);

    // Add file
    $client = new storage();
    $client->add('apex.zip', $tmp_file);
    $this->assertFileExists(SITE_PATH . '/storage/apex.zip');
    $this->assertFileIsWritable(SITE_PATH . '/storage/apex.zip');

    // Get exception
    $this->waitException('Unable to add file');
    $client->add('apex.zip', $tmp_file, 'public', false);

    // Delete tmp file
    @unlink($tmp_file);

}

/**
 * Add file from contents
 */
public function test_add_contents()
{

    // Delete if exists
    $file = SITE_PATH . '/storage/unit_test.txt';
    if (file_exists($file)) { 
        @unlink($file);
    }

    // Add file
    $client = new storage();
    $client->add_contents('unit_test.txt', 'I am Apex');
    $this->assertFileExists($file);
    $this->assertFileIsWritable($file);
    $this->assertFileContains($file, 'I am Apex');

    // Overrwrite file
    $client->add_contents('unit_test.txt', 'some random test');
    $this->assertFileExists($file);
    $this->assertFileIsWritable($file);
    $this->assertFileContains($file, 'random');

    // Get exception
    $this->waitException('Unable to add');
    $client->add_contents('unit_test.txt', 'test', 'public', false);

}

/**
 * has
 */
public function test_has()
{

    // Test
    $client = new storage();
    $this->assertTrue($client->has('apex.zip'));
    $this->assertFalse($client->has('some_junk_file_that_will_never_exist.txt'));

}

/**
 * get
 */
public function test_get()
{

    // Get composer.json file
    $client = new storage();
    $contents = $client->get('composer.json');
    $this->assertNotEmpty($contents);
    $this->assertStringContains($contents, 'address');

    // Get exception
    $this->waitException('Unable to read file');
    $client->get('some_junk_file.txt');

}

/**
 * Get stream
 */
public function test_get_stream()
{

    // Get tmp file
    $tmp_file = sys_get_temp_dir() . '/unit_test.zip';
    if (file_exists($tmp_file)) { @unlink($tmp_file); }

    // Open file
    $fh = fopen($tmp_file, 'wb+');

    // Get file stream
    $client = new storage();
    $stream = $client->get_stream('apex.zip');
    while ($buffer = fread($stream, 2048)) { 
        fwrite($fh, $buffer);
    }
    fclose($stream);
    fclose($fh);

    // Assert
    $this->assertFileExists($tmp_file);
    $this->assertFileIsReadable($tmp_file);
    $this->assertEquals((int) filesize($tmp_file), $client->get_size('apex.zip'));

    // Clean up
    @unlink($tmp_file);

    // Get exception
    $this->waitException('Unable to read file');
    $client->get_stream('some_junk_file_that_will_never_exist');

}

/**
 * delete
 */
public function test_delete()
{

    // Delete file
    $client = new storage();
    $client->delete('apex.zip');
    $this->assertFileNotExists(SITE_PATH . '/storage/apex.zip');

    // Delete non-existence file
    $this->waitException('Unable to delete file');
    $client->delete('some_junk_file');


}

/**
 * rename
 */
public function test_rename()
{

    // Rename file
    $client = new storage();
    $client->rename('composer.json', 'compose.json');
    $this->assertFileExists(SITE_PATH . '/storage/compose.json');
    $this->assertFileNotExists(SITE_PATH . '/storage/composer.json');

    // Throw exception
    $this->waitException('Unable to rename file');
    $client->rename('some_junk_file', 'some_other_file');

}

/**
 * copy
 */
public function test_copy()
{

    // Copy
    $client = new storage();
    $client->copy('compose.json', 'c.json');
    $this->assertFileExists(SITE_PATH . '/storage/c.json');
    $this->assertFileExists(SITE_PATH . '/storage/compose.json');

    // Get exception
    //$this->waitException("Unable to copy file');
    //$client->copy('junk_file', 'another_junk_file');

}

/**
 * create / delete directory
 */
public function test_create_delete_dir()
{

    // Create
    $client = new storage();
    $client->create_dir('my/unit/test');
    $this->assertDirectoryExists(SITE_PATH . '/storage/my/unit/test');

    // Delete directory
    $client->delete_dir('my/unit/test');
    $this->assertDirectoryNotExists('/storage/my/unit/test');

}

/**
 * Get mime type
 */
public function get_mime_type()
{

    // Check
    $client = new storage();
    $type = $client->get_mime_type('unit_test.txt');
    $this->assertEquals('text/plain', $type);

}

/**
 * Get timestamp
 */
public function test_get_timestamp()
{

    // Test
    $client = new storage();
    $time = $client->get_timestamp('unit_test.txt');
    $this->assertNotEmpty($time);

}

/**
 *  get_mime_type
 */
public function test_get_mime_type()
{

    $client = new storage();
    $type = $client->get_mime_type('c.json');
    $this->assertNotEmpty($type);

}

/**
 * Clean up
 */
public function test_cleanup()
{

    $files = array(
        'composer.json',
        'compose.json',  
        'c.json', 
        'apex.zip', 
        'unit_test.txt'
    );

    // Delete as needed
    foreach ($files as $file) { 
        if (!file_exists(SITE_PATH . '/storage/' . $file)) { continue; }
        @unlink(SITE_PATH . '/storage/' . $file);
    }

    // Delete tmp file
    $tmp_file = sys_get_temp_dir() . '/apex.zip';
    if (file_exists($tmp_file)) { @unlink($tmp_file); }

    // True
    $this->assertTrue(true);

}



}

