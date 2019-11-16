<?php
declare(strict_types = 1);

namespace apex\app\exceptions;

use apex\app;


/**
 * Handles all errors related to communications, such as invalid e-mail 
 * address, unable to connect to SMTP, unable to send SMS message via Nexcmo, 
 * etc. 
 */
class CommException   extends ApexException
{

    // Properties
    private $error_codes = array(
        'invalid_email' => "Invalid e-mail address was specified, {email}",
        'invalid_content_type' => "Invalid content-type specified for the e-mail message, {content_type}",
        'no_sender' => "Unable to determine sender to send e-mail notification, {recipient}",
        'no_recipient' => "Unable to determin recipient information for e-mail notification, {recipient}",
        'not_exists' => "Notification does not exist in database, ID# {id}"
    );

/**
 * Construct 
 *
 * @param string $message The exception message
 * @param string $email The recipient e-mail message
 * @param string $content_type The content type
 * @param string $recipient The recipient from the database (eg. user:32)
 * @param string $notification_id The ID# of the notification.
 */
public function __construct($message, $email = '', $content_type = '', $recipient = '', $notification_id = 0)
{ 

    // Set variables
    $vars = array(
        'id' => $notification_id,
        'email' => $email,
        'content_type' => $content_type,
        'recipient' => $recipient
    );

    // Get message
    $this->log_level = 'error';
    $this->message = $this->error_codes[$message] ?? $message;
    $this->message = tr($this->message, $vars);

}


}

