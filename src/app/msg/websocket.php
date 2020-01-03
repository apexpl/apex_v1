<?php
declare(strict_types = 1);

namespace apex\app\msg;

use apex\app;
use apex\libc\debug;
use apex\libc\msg;
use apex\app\msg\objects\event_message;
use apex\app\msg\objects\websocket_message;
use apex\app\interfaces\msg\WebSocketMessageInterface;


/**
 * Handles all web socket functionality, such as dispatching messages to 
 * connected browsers. 
 */
class websocket
{


/**
 * Send message via Web Socket. 
 *
 * Send message to internal Web Socket server, which is passed to necessary 
 * user's web browsers, and allows DOM elements within the page to be updated 
 * in real-time (eg. new notification, etc.) 
 *
 * @param WebSocketMessageInterface $msg The web socket message to send.
 */
public function dispatch(WebSocketMessageInterface $msg)
{ 

    // Debug
    debug::add(3, tr("Sending message to Web Socket server, area {1}, uri: {2}", $msg->get_area(), $msg->get_uri()));

    // Send WS message
    $msg = new event_message('core.notify.send_ws', $msg->get_json());
    $msg->set_type('direct');
    msg::dispatch($msg);

}


}

