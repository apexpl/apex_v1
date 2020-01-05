<?php
declare(strict_types = 1);

namespace apex\core\worker;

use apex\app;
use apex\libc\db;
use apex\libc\debug;
use apex\app\interfaces\msg\EventMessageInterface as event;

/**
 * Handles the unit test of chunked uploading a 
 * large file via the io::send_chunked_file() method.
 */
class unit_test_uploads
{

    // Routing key
    public $routing_key = 'core.files';



/**
 * Check for newly uploaded file.
 *
 * @param EventMessageInterface $msg The message that was dispatched.
 */
public function chunk_uploaded(event $msg)
{

    // Check filename
    $filename = $msg->get_params();
    if ($filename != 'unit_test-apex.zip') { return; }

    // Save file
    $filename = sys_get_temp_dir() . '/unit_test-apex.zip';
    rename($filename, SITE_PATH . '/utest/apex_up.zip');

    // Return
    return true;

}

}

