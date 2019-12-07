<?php
declare(strict_types = 1);

namespace apex\core\worker;

use apex\app;
use apex\svc\io;
use apex\svc\redis;
use apex\app\msg\emailer;
use apex\app\msg\utils\msg_utils;
use apex\app\interfaces\msg\EventMessageInterface as event;


/**
 * Handles various forms of notifications such as sending of e-mail, SMS and 
 * web socket messages. 
 */
class notify
{



/**
 * Send an e-mail message 
 *
 * @param EventMessageInterface $msg The message that was dispatched.
 * @param emailer $emailer The /app/msg/emailer.php class.  Injected.
 */
public function send_email(event $msg, emailer $emailer)
{ 

    // Initialize
    $email = $msg->get_params();

    // Send the e-mail message
    $emailer->dispatch_smtp($email);

}

/**
 * Send a SMS message via Nexmo 
 *
 * @param EventMessageInterface $msg The message that was dispatched.
 */
public function send_sms(event $msg)
{ 

    // Initialize
    $sms = $msg->get_params();

    // Set request
    $request = array(
        'api_key' => app::_config('core:nexmo_api_key'),
        'api_secret' => app::_config('core:nexmo_api_secret'),
        'from' => $sms->get_from_name(),
        'to' => $sms->get_phone(), 
        'text' => $sms->get_message()
    );

    // Set URL
    $url = str_replace("~domain_name~", app::_config('core:domain_name'), app::get('nexmo_api_url'));
    $url .= http_build_query($request);

    // Send request
    $response = io::send_http_request($url);
    $vars = json_decode($response, true);

    // Return
    $ok = preg_match("/\"error-text\":\"(.+?)\"/", $response, $match) ? false : true;
    return $ok;

}

/**
 * Send Web Socket message 
 *
 * @param EventMessageInterface $msg The message that was dispatched.
 * @param app $app The /src/app.php class.  Injected.
 */
public function send_ws(event $msg)
{ 

    // Initialize
    $data = $msg->get_params();

    // Start header
    $header = '1000' . sprintf('%04b', 1) . '1';
    $length = strlen($data);

    // Add length to header
    if ($length > 65535) { 
        $header .= decbin(127) . sprintf('%064b', $length);
    } elseif ($length > 125) { 
        $header .= decbin(126) . sprintf('%016b', $length);
    } else { 
        $header .= sprintf('%07b', $length);
    }

    // Start body of message
    $frame = '';
    foreach (str_split($header, 8) as $binstr) { 
        $frame .= chr(bindec($binstr));
    }

    // Add mask
    $mask = chr(rand(0, 255)) . chr(rand(0, 255)) . chr(rand(0, 255)) . chr(rand(0, 255));
    $frame .= $mask;

    // Add data to message
    for ($i = 0; $i < $length; $i++) { 
        $frame .= $data[$i] ^ $mask[$i % 4];
    }

    // Send message
    $host = redis::hget('config:rabbitmq', 'host') ?? '127.0.0.1';
    try { 
        if (!$sock = @fsockopen($host, (int) app::_config('core:websocket_port'), $errno, $errstr, 3)) { 
            return true;
            }
    } catch (Exception $e) { 
        return true;
    }

    // Write data
    fwrite($sock, $frame);
    fclose($sock);

}


}

