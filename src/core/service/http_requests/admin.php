<?php
declare(strict_types = 1);

namespace apex\core\service\http_requests;

use apex\app;
use apex\libc\db;
use apex\libc\view;
use apex\libc\auth;
use apex\core\service\http_requests;


/**
 * Handle admin panel HTTP request 
 *
 * Handles all HTTP requests to the administration panel, and ensure the user 
 * is an authenticated administrator. 
 */
class admin extends http_requests
{


/**
 * Process admin panel HTTP request 
 *
 * Processes all HTTP requests send to http://domain.com/admin/ Simply checks 
 * authentication, then passes the request off to the template engine. Also 
 * checks if an administrator exists, and if not, prompts to create the first 
 * administrator. 
 *
 * @param app $app The /src/app.php class.  Injected.
 */
public function process()
{ 

    // Check if admin enabled
    if (ENABLE_ADMIN == 0 || (app::has_get('disable_admin') && app::_get('disable_admin') == 1)) { 
        app::set_res_http_status(404);
        app::set_uri('404', false, true);
        app::set_res_body(view::parse());
        return;
    }

    // Set area and theme
    app::set_area('admin');
    app::set_theme(app::_config('core:theme_admin'));

    //  Check if admin exists
    $count = db::get_field("SELECT count(*) FROM admin");
    if (app::get_action() == 'create' && $count == 0) { 

        // Create first admin
        $client = app::make(\apex\core\admin::class);
        if (!$userid = $client->create()) { 
            app::set_uri('admin/create_first_admin');
            app::set_res_body(view::parse());
            return;
        }

        auth::auto_login((int) $userid);
        return;

    // Display form to create first admin
    } elseif ($count == 0) { 
        app::set_uri('admin/create_first_admin');
        app::set_res_body(view::parse());
        return;
    }

    // Check auth
    if (!auth::check_login(true)) { 
        return;
    }

    // Parse template
    app::set_res_body(view::parse());

}


}

