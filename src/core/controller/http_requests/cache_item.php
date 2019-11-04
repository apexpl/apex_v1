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
    if (!$vars = cache::get($item)) { 

        // Get variables
        $parts = explode(":", $item);

        // Check for theme
        if ($parts[0] == 'theme') { 

            // Get filename
            $theme_alias = $parts[1] == 'members' ? app::_config('users:theme_members') : app::_config('core:theme_' . $parts[1]);
            $file = SITE_PATH . '/public/themes/' . $theme_alias . '/' . $parts[2];

            // Get file contents, and add to cache
            if (file_exists($file)) {
                $contents = file_get_contents($file);

                // Add item to cache
                $vars = array(
                    'type' => mime_content_type($file), 
                    'contents' => $contents
                );
                cache::set($item, $contents);
            }
        }

    }

    // Set response
    app::set_res_content_type($vars['type']);
    app::set_res_body($vars['contents']);

}


}




