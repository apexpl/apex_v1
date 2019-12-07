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

    // Return
    return true;

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
    $message = $this->format_ws_message($data);

    // Get URL
    $host = 'tcp://' . redis::hget('config:rabbitmq', 'host');
    $port = app::_config('core:websocket_port');

    // Send message
    try {
        $sock = @fsockopen($host, (int) $port, $errno, $errstr, 5);
    } catch (Exception $e) {
        return false;
    }
    if (!$sock) { return false; }
    stream_set_timeout($sock, 5);

    // Send headers
    fwrite($sock, "GET / HTTP/1.1\r\n");
    fwrite($sock, "Host: " . $host . ':' . $port . "\r\n");
    fwrite($sock, "user-agent: websocket-client-php\r\n");
    fwrite($sock, "connection: Upgrade\r\n");
    fwrite($sock, "upgrade: websocket\r\n");
    fwrite($sock, "sec-websocket-key: " . $this->generate_ws_key() . "\r\n");
    fwrite($sock, "sec-websocket-version: 13\r\n\r\n");

    // Get response
    $response = fread($sock, 1024);
    $metadata = stream_get_meta_data($sock);

    // Send the message
    fwrite($sock, $message);
    fclose($sock);

    // Return
    return true;


}

/**
 * Get WebSocket message.
 *
 */
private function format_ws_message($payload, $opcode = 'text', $masked = true)
{

    //if (!in_array($opcode, array_keys(self::$opcodes))) {
      //throw new BadOpcodeException("Bad opcode '$opcode'.  Try 'text' or 'binary'.");
    //}

    // Binary string for header.
    $frame_head_binstr = '';


    // Write FIN, final fragment bit.
    $final = true; /// @todo Support HUGE payloads.
    $frame_head_binstr .= $final ? '1' : '0';

    // RSV 1, 2, & 3 false and unused.
    $frame_head_binstr .= '000';

    // Opcode rest of the byte.
    $frame_head_binstr .= sprintf('%04b', 1);

    // Use masking?
    $frame_head_binstr .= $masked ? '1' : '0';

    // 7 bits of payload length...
    $payload_length = strlen($payload);
    if ($payload_length > 65535) {
      $frame_head_binstr .= decbin(127);
      $frame_head_binstr .= sprintf('%064b', $payload_length);
    }
    elseif ($payload_length > 125) {
      $frame_head_binstr .= decbin(126);
      $frame_head_binstr .= sprintf('%016b', $payload_length);
    }
    else {
      $frame_head_binstr .= sprintf('%07b', $payload_length);
    }

    $frame = '';

    // Write frame head to frame.
    foreach (str_split($frame_head_binstr, 8) as $binstr) $frame .= chr(bindec($binstr));

    // Handle masking
    if ($masked) {
      // generate a random mask:
      $mask = '';
      for ($i = 0; $i < 4; $i++) $mask .= chr(rand(0, 255));
      $frame .= $mask;
    }

    // Append payload to frame:
    for ($i = 0; $i < $payload_length; $i++) {
      $frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
    }

    // Return
    return $frame;

}

/**
 * Generate WebSocket key
 */
private function generate_ws_key()
{

    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!"$&/()=[]{}0123456789';
    $key = '';
    $chars_length = strlen($chars);
    for ($i = 0; $i < 16; $i++) $key .= $chars[mt_rand(0, $chars_length-1)];
    return base64_encode($key);

}

}


