<?php
declare(strict_types = 1);

namespace apex\app\msg;

use apex\app;
use apex\libc\msg;
use apex\app\msg\objects\event_message;
use apex\app\interfaces\msg\SMSMessageInterface;


/**
 * Class that handles sending of SMS messages via Nexmo.
 */
class sms
{

/**
 * Dispatch a SMS message 
 *
 * @param SMSMessageInterface $msg The SMS message to send.
 */
public function dispatch(SMSMessageInterface $sms)
{ 

    // Send it
    $msg = new event_message('core.notify.send_sms', $sms);
    $ok = msg::dispatch($msg)->get_response('core');

    // Return
    return $ok;

}

}


