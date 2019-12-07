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
    $item_data = cache::get($item);
    if ($item_data === false) { 

        // Get variables
        $parts = explode(":", $item);
        $type = array_shift($parts);

        // Check for theme
        if ($type == 'theme') { 

            // Get filename
            $area = array_shift($parts);
            $theme_alias = $type == 'members' ? app::_config('users:theme_members') : app::_config('core:theme_' . $area);
            $file = SITE_PATH . '/public/themes/' . $theme_alias . '/' . preg_replace("/\?(.+)$/", "", $parts[0]);

            // Get file contents, and add to cache
            if (file_exists($file)) {
                $contents = file_get_contents($file);

                // Get vars
                $vars = array(
                    'type' => mime_content_type($file), 
                    'contents' => $contents
                );
                if (preg_match("/^text/", $vars['type'])) { $vars['type'] = 'text/plain'; }

                // Set item into cache
                cache::set($item, serialize($vars));
            }
        }

    // Unserialize
    } else { 
        $vars = unserialize($item_data);
    }

    // Set response
    if (preg_match("/^text/", $vars['type'])) { $vars['type'] = 'text/plain'; }
    app::set_res_content_type($vars['type']);
    app::set_res_body($vars['contents']);

}


}




