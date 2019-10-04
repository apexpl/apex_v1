<?php
declare(strict_types = 1);


namespace apex\app\interfaces\msg;

use apex\app\web\ajax;


/**
 * Web socket message interface.
 */
interface WebSocketMessageInterface
{

    /**
     * Get the AJAX actions / results
     */
    public function get_ajax();

    /**
     * Get recipients
     * 
     *     @return array The list of recipients
     */
    public function get_recipients();

    /**
     * Get the area
     * 
     *     @return string The area to send WS message to
     */
    public function get_area();

    /**
     * Get the URI
     *
     *      @return string $uri The URI to send WS message to
     */
    public function get_uri();

    /**
     * Get JSON of the message.  This is what is sent to the web socket server.
     *
     *     @return string JSON encoded string to send to WS server
     */
    public function get_json();


}

