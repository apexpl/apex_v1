<?php

namespace apex\app\interfaces\msg;

use apex\app\interfaces\msg\EmailMessageInterface;


/** 
 * E-Mailer Interface
 */
interface EmailerInterface 
{

/**
 * Send an e-mail message. 
 *
 * @param EventMessageInterface $msg The e-mail message to send.
 */
public function dispatch(EmailMessageInterface $msg);


/**
 * Processes any necessary notifications.  Takes in the type of notifications, 
 * and additional variables.  Checks each notification against the condition, 
 * and sends any that match. 
 *
 * @param string $controller The notification controller / type alias of which to check.
 * @param int $userid The user ID# for which notifications are being processed against.
 * @param array $condition Associative array containing details on the current request, and is checked against the condition notifications were created with.
 * @param array $data Associatve array that is passed to the notification controller, and contains any additional information to retrieve merge variables (eg. transaction ID#, support ticket ID#, etc.)
 */
public function process_emails(string $controller, int $userid = 0, array $condition = array(), array $data = array());


/**
 * Send single e-mail message. 
 *
 * Send a single e-mail message vir rotating SMTP servers, or if not SMTP 
 * servers available via phpmail().  Dispatches the e-mail to a listener to 
 * take advantage of horizontal scaling with RabbitMQ. 
 *
 * @param string $to_email E-mail address of the recipient.
 * @param string $to_name Full name of the recipient.
 * @param string $from_email E-mail address of the sender.
 * @param string $from_name Full name of the sender.
 * @param string $subject The subject of the e-mail message.
 * @param string $message The contents of the e-mail message to send.
 * @param string $content_type Optional, and the content type of the e-mail message.  Defaults to "text/plain".
 * @param string $reply_to Optional, the the Reply-To e-mail address.
 * @param string $cc Optional, and the e-mail address to CC the message to.
 * @param string $bcc Optional, and the e-mail address to BCC the message to.
 * @param array $attachments Optional, and associative array of file attachments to include, keys are the filename and value is contents of file.
 */
public function send(string $to_email, string $to_name, string $from_email, string $from_name, string $subject, string $message, string $content_type = 'text/plain', string $reply_to = '', string $cc = '', string $bcc = '', array $attachments = array());

}


