<?php
declare(strict_types = 1);

namespace apex\app\msg;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\svc\redis;
use apex\app\web\ajax;
use apex\app\msg\websocket;
use apex\app\msg\objects\websocket_message;


/**
 * Handles adding of different alerts, mainly dropdown notifications and 
 * messages. 
 */
class alerts
{


    // Properties
    private $websocket;


/**
 * Construct. 
 *
 * @param websocket $websocket The web socket message to dispatch.
 */
public function __construct(websocket $websocket)
{ 
    $this->websocket = $websocket;
}

/**
 * Add new notification alert 
 *
 * @param string $recipient The recipient of the alert in standard format (eg. user:915, admin:3, etc.)
 * @param string $message The body of the alert message
 * @param string $url The URL to link the drop-down notification to
 */
public function dispatch_notification(string $recipient, string $message, string $url)
{ 

    // Debug
    debug::add(3, tr("Adding notification / alert via Web Socket to recipient: {1}", $recipient));

    // Set vars
    $vars = array(
        'message' => $message,
        'url' => $url,
        'time' => time()
    );

    // Get redis key
    $redis_key = 'alerts:' . $recipient;
    redis::lpush($redis_key, json_encode($vars));
    redis::ltrim($redis_key, 0, 20);
    $unread_alerts = redis::hincrby('unread:alerts', $recipient, 1);

    // Get HTML of dropdown item
    $comp_file = SITE_PATH . '/views/themes/' . app::get_theme() . '/components/dropdown_alert.tpl';
    if (file_exists($comp_file)) { 
        $html = file_get_contents($comp_file);
    } else { 
        $html = '<li><a href="~url~"><p>~message~<br /><i style="font-size: small">~time~</i><br /></p></a></li>';
    }

    // Merge variables
    $html = str_replace("~url~", $url, $html);
    $html = str_replace("~message~", $message, $html);
    $html = str_replace("~time~", 'Just Now', $html);

    // AJAX
    $ajax = new ajax();
    $ajax->prepend('dropdown_alerts', $html);
    $ajax->set_text('badge_unread_alerts', $unread_alerts);
    $ajax->set_display('badge_unread_alerts', 'block');
    $ajax->play_sound('notify.wav');

    // Send WS message
    $msg = new websocket_message($ajax, array($recipient));
    $this->websocket->dispatch($msg);

}

/**
 * Add new dropdown notification message 
 *
 * @param string $recipient The recipient of the alert in standard format (eg. user:915, admin:3, etc.)
 * @param string $from Who the message is from.
 * @param string $message The body of the alert message
 * @param string $url The URL to link the drop-down notification to
 */
public function dispatch_message(string $recipient, string $from, string $message, string $url)
{ 

    // Debug
    debug::add(3, tr("Adding dropdown notification via Web Socket to recipient: {1}", $recipient));

    // Set vars
    $vars = array(
        'from' => $from,
        'message' => $message,
        'url' => $url,
        'time' => time()
    );

    // Get recipient
    $recipients = array();
    if ($recipient == 'admin') { 
        $admin_ids = DB::get_column("SELECT id FROM admin");
        foreach ($admin_ids as $admin_id) { $recipients[] = 'admin:' . $admin_id; }
    } else { $recipients[] = $recipient; }

    // Get redis key
    foreach ($recipients as $recipient) { 
        $redis_key = 'messages:' . $recipient;
        redis::lpush($redis_key, json_encode($vars));
        redis::ltrim($redis_key, 0, 9);
        $unread_messages = redis::hincrby('unread:messages', $recipient, 1);
    }

    // Get HTML of dropdown item
    $comp_file = SITE_PATH . '/views/themes/' . app::get_theme() . '/components/dropdown_message.tpl';
    if (file_exists($comp_file)) { 
        $html = file_get_contents($comp_file);
    } else { $html = '<li><a href="~url~"><p><b>~from~</b><br />~message~<br /><i style="font-size: small">~time~</i><br /></p></a></li>'; }

    // Merge variables
    $html = str_replace("~from~", $from, $html);
    $html = str_replace("~url~", $url, $html);
    $html = str_replace("~message~", $message, $html);
    $html = str_replace("~time~", 'Just Now', $html);

    // AJAX
    $ajax = new ajax();
    $ajax->prepend('dropdown_messages', $html);
    $ajax->set_text('badge_unread_messages', $unread_messages);
    $ajax->set_display('badge_unread_messages', 'block');
    $ajax->play_sound('notify.wav');

    // Send WS message
    $msg = new websocket_message($ajax, $recipients);
    $this->websocket->dispatch($msg);

}


}

