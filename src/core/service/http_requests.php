<?php
declare(strict_types = 1);

namespace apex\core\service;

/**
 * Parent abstract class for the service, used to help 
 * control the flow of data to / from adapters, and define the abstract methods required 
 * by adapters.
 */
class http_requests
{

    /**
     * Optionally define a BASE64 encoded string here, and this will be used as the default code 
     * for all adapters created for this service.
     * 
     * Use the merge field http_requests 
     * for the class name, and it will be replaced appropriately.
     */
    public $default_code = 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XGNvcmVcc2VydmljZVxodHRwX3JlcXVlc3RzOwoKdXNlIGFwZXhcYXBwOwp1c2UgYXBleFxsaWJjXGRiOwp1c2UgYXBleFxsaWJjXGRlYnVnOwp1c2UgYXBleFxjb3JlXHNlcnZpY2VcaHR0cF9yZXF1ZXN0czsKCi8qKgogKiBIVFRQIGFkYXB0ZXIKICovCmNsYXNzIH5hbGlhc34gZXh0ZW5kcyBodHRwX3JlcXVlc3RzCnsKCi8qKgogKiBQcm9jZXNzIHRoZSBIVFRQIHJlcXVlc3QuCiAqCiAqIE1pZGRsZXdhcmUgY2xhc3MgdG8gaGFuZGxlIHRoZSBIVFRQIHJlcXVlc3QgZm9yIGFueSBVUklzIHRoYXQgZ290byAvfmFsaWFzfi8qLiAgVXNlIHRoZSAKICogYXBwOjpnZXRfdXJpX3NlZ21lbnRzKCkgYW5kIGFwcDo6Z2V0X3VyaSgpIG1ldGhvZHMgdG8gZGV0ZXJtaW5lIHRoZSBleGFjdCBVUkkgYmVpbmcgCiAqIHJlcXVlc3RlZCwgYW5kIHByb2Nlc3MgYWNjb3JkaW5nbHkuICBVc2UgdGhlIGZvbGxvd2luZyBtZXRob2RzIHRvIAogKiBzZXQgYSByZXNwb25zZSBmb3IgdGhlIHJlcXVlc3QuCiAqICAgIGFwcDo6c2V0X3Jlc19jb250ZW50cygpCiAqICAgIGFwcDo6c2V0X3Jlc19odHRwX3N0YXR1cygpCiAqICAgIGFwcDo6c2V0X3Jlc19jb250ZW50X3R5cGUoKQogKi8KcHVibGljIGZ1bmN0aW9uIHByb2Nlc3MoKQp7CgogICAgLy8gR2V0IHJlc3BvbnNlCiAgICAkcmVzcG9uc2UgPSAnJzsKCiAgICAvLyBTZXQgcmVzcG9uc2UKICAgIGFwcDo6c2V0X3Jlc19ib2R5KCRyZXNwb25zZSk7Cgp9Cgp9CgoKCg==';

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
//public function process() { } 

}

