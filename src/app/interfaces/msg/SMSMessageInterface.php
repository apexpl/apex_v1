<?php
declare(strict_types = 1);


namespace apex\app\interfaces\msg;

/**
 * SMS Message interface.
 */
interface SMSMessageInterface
{


    /**
     * Get the phone number
     * 
     *     @return string The recipient phone number.
     */
    public function get_phone();

    /**
     * Get the message
     * 
     *     @return string The SMS message contents
     */
    public function get_message();

    /**
     * Get the from name.
     *
     *     @return string The from name
     */
    public function get_from_name();



}

