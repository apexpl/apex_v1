<?php
declare(strict_types = 1);

namespace apex\core\service\http_requests;

use apex\app;
use apex\libc\view;
use apex\libc\auth;
use apex\core\service\http_requests;

/**
 * Default HTTP adapter 
 *
 * Used as a catch-all, and handles all HTTP requests to any URI for which 
 * their is not a specific http_requests adapter defined. Treats all 
 * requests as a page on the public web site. 
 */
class http
{


/**
 * Handle the http request, and parse the correct template from the public web 
 * site. 
 */
public function process()
{ 

    // Set theme
    app::set_theme(app::_config('core:theme_public'));
    app::set_area('public');

    // Check login
    auth::check_login(false);

    // Parse template
    app::set_res_body(view::parse());

}


}

