<?php
declare(strict_types = 1);

namespace apex\core\controller\http_requests;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\svc\cache;
use apex\core\controller\http_requests;


/**
 * Handles caching of theme based assets (CSS, Javsc ript, images), plus 
 * handles caching of images stored via the apex\svc\images library.
 */
class cache_item
{

/**
 * Process the cache request
 */
public function process()
{

    // Initialize
    $item = preg_replace("/^cache_item\//", "", app::get_uri());

    // check cache for item
    if (!$item_data = cache::get($item)) { 

        // Get variables
        $parts = explode(":", $item);
        $type = array_shift($parts);

        // Check for theme
        if ($type == 'theme') { 


            // Get file
            $file = SITE_PATH . '/public/themes/' . $parts[0];
            if (!file_exists($file)) { 
                app::set_res_body("Cache Error:  No $type file exists at: $parts[0]\n");
                return;
            }


            // Get item data
            $item_data = array(
                'type' => mime_content_type($file), 
                'contents' => file_get_contents($file)
            );
            cache::set($item, $item_data);
        }
    }

    // Set response
    app::set_res_content_type($item_data['type']);
    app::set_res_body($item_data['contents']);

}


}




