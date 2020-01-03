<?php
declare(strict_types = 1);

namespace apex\core\service\http_requests;

use apex\app;
use apex\libc\db;
use apex\libc\debug;
use apex\libc\images;
use apex\core\service\http_requests;


/**
 * HTTP adapter that handles the retrieval and siplaying of 
 * images from the apex\libc\images library.
 */
class image extends http_requests
{


/**
 * Pulls specified image from the 'images' database table, and properly 
 * displays it to the browser. Meant to allow easy image management and 
 * storage via the /lib/image.php class. 
 */
public function process()
{ 

    // Get size
    $parts = app::get_uri_segments();
    $size = isset($parts[2]) ? preg_replace("/\.(.+)$/", "", $parts[2]) : 'full';

    // Display image
    images::display($parts[0], $parts[1], $size);

}


}

