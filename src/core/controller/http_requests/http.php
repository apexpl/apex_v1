<?php
declare(strict_types = 1);

namespace apex\core\controller\http_requests;

use apex\app;
use apex\svc\view;
use apex\svc\auth;
use apex\core\controller\http_requests;


/**
 * Default HTTP controller 
 *
 * Used as a catch-all, and handles all HTTP requests to any URI for which 
 * their is not a specific http_requests controller defined. Treats all 
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

