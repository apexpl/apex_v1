<?php
declare(strict_types = 1);

namespace apex\core\controller;

use apex\app;
use apex\svc\db;
use apex\svc\debug;

/**
 * HTTP controller
 */
class http_requests
{

    // Default code
    public $default_code = 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XGNvcmVcY29udHJvbGxlclxodHRwX3JlcXVlc3RzOwoKdXNlIGFwZXhcYXBwOwp1c2UgYXBleFxzdmNcZGI7CnVzZSBhcGV4XHN2Y1xkZWJ1ZzsKdXNlIGFwZXhcY29yZVxjb250cm9sbGVyXGh0dHBfcmVxdWVzdHM7CgovKioKICogSFRUUCBjb250cm9sbGVyCiAqLwpjbGFzcyB+YWxpYXN+IGV4dGVuZHMgaHR0cF9yZXF1ZXN0cwp7CgovKioKICogUHJvY2VzcyB0aGUgSFRUUCByZXF1ZXN0LgogKgogKiBNaWRkbGV3YXJlIGNsYXNzIHRvIGhhbmRsZSB0aGUgSFRUUCByZXF1ZXN0IGZvciBhbnkgVVJJcyB0aGF0IGdvdG8gL35hbGlhc34vKi4gIFVzZSB0aGUgCiAqIGFwcDo6Z2V0X3VyaV9zZWdtZW50cygpIGFuZCBhcHA6OmdldF91cmkoKSBtZXRob2RzIHRvIGRldGVybWluZSB0aGUgZXhhY3QgVVJJIGJlaW5nIAogKiByZXF1ZXN0ZWQsIGFuZCBwcm9jZXNzIGFjY29yZGluZ2x5LiAgVXNlIHRoZSBmb2xsb3dpbmcgbWV0aG9kcyB0byAKICogc2V0IGEgcmVzcG9uc2UgZm9yIHRoZSByZXF1ZXN0LgogKiAgICBhcHA6OnNldF9yZXNfY29udGVudHMoKQogKiAgICBhcHA6OnNldF9yZXNfaHR0cF9zdGF0dXMoKQogKiAgICBhcHA6OnNldF9yZXNfY29udGVudF90eXBlKCkKICovCnB1YmxpYyBmdW5jdGlvbiBwcm9jZXNzKCkKewoKICAgIC8vIEdldCByZXNwb25zZQogICAgJHJlc3BvbnNlID0gJyc7CgogICAgLy8gU2V0IHJlc3BvbnNlCiAgICBhcHA6OnNldF9yZXNfYm9keSgkcmVzcG9uc2UpOwoKfQoKfQoKCgo=';

/**
 * Process the HTTP request.
 *
 * Middleware class to handle the HTTP request for any URIs that goto /~alias~/*.  Use the 
 * app::get_uri_segments() and app::get_uri() methods to determine the exact URI being 
 * requested, and process accordingly.  Use the following methods to 
 * set a response for the request.
 *    app::set_res_contents()
 *    app::set_res_http_status()
 *    app::set_res_content_type()
 */
public function process()
{

    // Process request
    $response = '';

    // Set response
    app::set_res_body($response);

}


}



