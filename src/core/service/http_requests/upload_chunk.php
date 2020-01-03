<?php
declare(strict_types = 1);

namespace apex\core\service\http_requests;

use apex\app;
use apex\libc\db;
use apex\libc\debug;
use apex\libc\msg;
use apex\app\msg\objects\event_message;
use apex\core\service\http_requests;


/**
 * Handles the uploading of chunked files.
 */
class upload_chunk extends http_requests
{


/**
 *  Process the HTTP request
 */
public function process()
{

    // Initialize
    $parts = app::get_uri_segments();
    if (count($parts) != 3) { echo "Invalid request"; exit(0); }

    // Set variables
    $filename = sys_get_temp_dir() . '/' . $parts[0];
    $chunk_num = (int) $parts[1];
    $total_chunks = (int) $parts[2];

    // Delete file, if exists and is first chunk
    if ($chunk_num == 1 && file_exists($filename)) { 
        @unlink($filename);
    }

    // Open file for writing
    if (!$fh = fopen($filename, 'ab+')) { 
        throw new IOException('file_no_write', $filename);
    }

    // Write contents to file, and close
    $contents = base64_decode(app::_post('contents'));
    fwrite($fh, $contents);
    fclose($fh);

    // Dispatch event message if file is done.
    if ($chunk_num >= $total_chunks) {
        $msg = new event_message('core.files.chunk_uploaded', $parts[0]);
        msg::dispatch($msg);
    }

    // Echo
    app::set_res_body($parts[0]);

}

}

