<?php
declare(strict_types = 1);

namespace apex\app\msg\objects;

use apex\app;
use apex\app\interfaces\msg\SMSMessageInterface;


/**
 * Defines a SMS message all all properties of it. 
 */
class sms_message implements SMSMessageInterface
{


    // Properties
    private $from_name;
    private $phone;
    private $message;

/**
 * Constructor.  Define the SMS message. 
 *
 * @param string $phone The recipient phone number.
 * @param string $message The message to send.
 * @param string $from_name The sender name.
 */
public function __construct(string $phone, string $message, string $from_name = '')
{ 

    // Set properties
    $this->phone = $phone;
    $this->message = $message;
    $this->from_name = $from_name == '' ? app::_config('core:site_name') : $from_name;

}

/**
 * Get the phone number 
 *
 * @return string The recipient phone number.
 */
public function get_phone() { return $this->phone; }

/**
 * Get the message 
 *
 * @return string The SMS message contents
 */
public function get_message() { return $this->message; }

/**
 * Get the from name. 
 *
 * @return string The from name
 */
public function get_from_name() { return $this->from_name; }


}

