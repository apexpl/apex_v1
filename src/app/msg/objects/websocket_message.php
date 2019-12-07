<?php
declare(strict_types = 1);

namespace apex\app\msg\objects;

use apex\app;
use apex\svc\debug;
use apex\app\web\ajax;
use apex\app\interfaces\msg\WebSocketMessageInterface;


/**
 * A web socket message interface class. 
 */
class websocket_message implements WebSocketMessageInterface
{


    // Properties
    private $ajax;
    private $recipients = [];
    private $area;
    private $uri;

/**
 * Define a new web socket message. 
 *
 * @param ajax $ajax The Ajax object that contains actions to perform in web browser.
 * @param array $recipients List of individual recipients to send message to.
 * @param string $area The area to dispatch message to.
 * @param string $uri The URI to dispatch message to.
 */
public function __construct(ajax $ajax, array $recipients = [], string $area = '', string $uri = '')
{ 

    // Set properties
    $this->ajax = $ajax;
    $this->recipients = $recipients;
    $this->area = $area;
    $this->uri = $uri;

}

/**
 * Get the AJAX actions / results 
 */
public function get_ajax() { return $this->ajax->results; }

/**
 * Get recipients 
 *
 * @return array The list of recipients
 */
public function get_recipients() { return $this->recipients; }

/**
 * Get the area 
 *
 * @return string The area to send WS message to
 */
public function get_area() { return $this->area; }

/**
 * Get the URI 
 *
 * @return string $uri The URI to send WS message to
 */
public function get_uri() { return $this->uri; }

/**
 * Get JSON of the message.  This is what is sent to the web socket server. 
 *
 * @return string JSON encoded string to send to WS server
 */
public function get_json()
{ 

    // Set vars
    $vars = array(
        'status' => 'ok',
        'actions' => $this->ajax->results,
        'recipients' => $this->recipients,
        'area' => $this->area,
        'uri' => trim($this->uri, '/'), 
        'reqtype' => app::get_reqtype()
    );

    // Return
    return json_encode($vars);

}


}

