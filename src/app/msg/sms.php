<?php
declare(strict_types = 1);

use apex\app;
use apex\app\interfaces\msg\SMSMessageInterface;


/**
 * Dispatch a SMS message 
 *
 * @param SMSMessageInterface $msg The SMS message to send.
 */
public function dispatch(SMSMessageInterface $sms)
{ 

    // Send it
    $msg = new event_message('core.notify.send_sms', $sms);
    $msg->set_type('direct');
    msg::dispatch($msg);

}

