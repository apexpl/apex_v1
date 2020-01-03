<?php
declare(strict_types = 1);

namespace apex\core\service\http_requests;

use apex\app;
use apex\core\service\http_requests;


/**
 * Remove API for the core Apex platform.
 */
class apex_api extends http_requests
{




/**
 * Handle Apex API request 
 *
 * Blank PHP class for the adapter.  For the correct methods and properties 
 * for this class, please review the abstract class located at: 
 * /src/core/adapter/core.php 
 */
public function process()
{ 

    // Set content type
    app::set_res_content_type('text/json');

    // Server check
    if (app::get_uri_segments()[0] == 'server_check') { 
        app::set_res_body(app::_config('core:server_status'));

    }




}


}

