<?php

/**
 * Load composer, so we have all of our goodies available to us.
 */
require_once(__DIR__ . '/../vendor/autoload.php');

/**
 * Initialize the application.  This will get everything loaded, 
 * our container built, services assigned, everything sanitized, 
 * redis connection established, and so on to get ready to handle the request.
 */
$app = new \apex\app('http');

/**
 * Pass the request off to the correct HTTP controller, 
 * which is dependant on the first segment of the URI.
 */
$app::call(["apex\\core\\service\\http_requests\\" . $app->get_http_controller(), 'process']);

/**
 * Now that the request is handled, go ahead and 
 * output the response to the web browser.
 */
$app::echo_response();

/**
 * Gracefully exit.
 */
exit(0);

