<?php
declare(strict_types = 1);

namespace apex\app\msg;

use apex\app;
use apex\svc\db;
use apex\svc\redis;
use apex\svc\debug;
use apex\svc\msg;
use apex\app\msg\utils\smtp_connections;
use apex\app\msg\objects\email_message;
use apex\app\msg\objects\event_message;
use apex\core\notification;
use apex\app\interfaces\msg\EmailMessageInterface;
use apex\app\exceptions\CommException;


/**
 * Handles sending and formatting of individual e-mail messages through the 
 * configured SMTP servers on the system 
 */
class emailer extends smtp_connections
{



    /**
     * @Inject
     * @var app
     */
    private $app;

    // Properties
    private $headers;
    private $server_num;
    private $smtp_host;


/**
 * Send an e-mail message. 
 *
 * @param EventMessageInterface $msg The e-mail message to send.
 */
public function dispatch(EmailMessageInterface $msg)
{ 

    // Send message via RabbitMQ
    $msg = new event_message('core.notify.send_email', $msg);
    $msg->set_type('direct');
    msg::dispatch($msg);

    // Return
    return true;

}

/**
 * Send the actual e-mail message via SMTP 
 *
 * @param EmailMessageInterface $msg The e-mail message to send
 */
public function dispatch_smtp(EmailMessageInterface $msg)
{ 

    // Format message
    $msg->format_message();

    // Get SMTP server
    if (!$smtp_vars = $this->get_smtp_server()) { 
        $this->dispatch_phpmail($msg);
        return false;
    }

    // Set variables
    $smtp = $smtp_vars['connection'];
    $this->server_num = $smtp_vars['server_num'];
    $this->smtp_host = $smtp_vars['host'];

    // MAIL FROM
    $response = $this->write($smtp, "MAIL FROM: <" . $msg->get_from_email() . ">");
    if (!preg_match("/^250/", $response) && !preg_match("/^235/", $response)) { 
        $this->queue_mail();
        return false;
    }

    // RCPT TO
    $response = $this->write($smtp, "RCPT TO: <" . $msg->get_to_email() . ">");
    if (!preg_match("/^250/", $response)) { 
        $this->queue_mail();
        return false;
    }

    // DATA
    $response = $this->write($smtp, "DATA");
    if (!preg_match("/^354/", $response)) { 
        $this->queue_mail();
        return false;
    }

    // Message contents
    fwrite($smtp, "To: " . $msg->get_recipient_line() . "\r\n");
    fwrite($smtp, $msg->get_headers());
    fwrite($smtp, "Subject: " . $msg->get_subject() . "\r\n\r\n");
    fwrite($smtp, $msg->get_message());
    fwrite($smtp, "\r\n.\r\n");

    // Check response
    $response = fread($smtp, 1024);
    if (!preg_match("/^250/", $response)) { 
        $this->queue_mail();
        return false;
    }

    // Return
    return true;

}

/**
 * Send e-mail via php mail() function if not SMTP is available. 
 *
 * @param EmailMessageInterface $msg The e-mail message to send.
 */
private function dispatch_phpmail(EmailMessageInterface $msg)
{ 

    // Send it
    mail($msg->get_recipient_line(), $msg->get_subject(), $msg->get_message(), $msg->get_headers());

}

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
public function process_emails(string $controller, int $userid = 0, array $condition = array(), array $data = array())
{ 

    // Debug
    debug::add(3, tr("Checking e-mail notifications for controller {1}, user ID# {2}", $controller, $userid));

    // Check for notifications
    $controller_alias = $controller;
    $rows = DB::query("SELECT * FROM notifications WHERE controller = %s ORDER BY id", $controller);
    foreach ($rows as $row) { 

        // Get conditions
        $ok = true;
        $chk_condition = json_decode(base64_decode($row['condition_vars']), true);
        foreach ($chk_condition as $key => $value) { 
            if (!isset($condition[$key])) { continue; }
            if ($condition[$key] == '' || $value == '') { continue; }
            if ($value != $condition[$key]) { $ok = false; break; }
        }
        if ($ok === false) { continue; }

        // Send notification
        $client = app::make(notification::class);
        $client->send($userid, $row['id'], $data);
    }

}

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
public function send(string $to_email, string $to_name, string $from_email, string $from_name, string $subject, string $message, string $content_type = 'text/plain', string $reply_to = '', string $cc = '', string $bcc = '', array $attachments = array())
{ 

    // Debug
    debug::add(4, tr("Sending e-mail message to {1} from {2} with subject: {3}", $to_email, $from_email, $subject));

    // Create e-mail message
    $msg = new email_message();
    $msg->to_email($to_email);
    $msg->to_name($to_name);
    $msg->from_email($from_email);
    $msg->from_name($from_name);
    $msg->subject($subject);
    $msg->message($message);
    $msg->content_type($content_type);

    // Add reply-to, cc, bcc
    if ($reply_to != '') { $msg->reply_to($reply_to); }
    if ($cc != '') { $msg->cc($cc); }
    if ($bcc != '') { $msg->bcc($bcc); }

    // Add attachments
    foreach ($attachments as $filename => $contents) { 
        $msg->add_attachment($filename, $contents);
    }

    // Dispatch the message
    $this->dispatch($msg);

}

/**
 * Queue undeliverable mail 
 */
private function queue_mail()
{ 
    return;

    // Set variables
    $has_attachments = 0;

    // Add to DB
    db::insert('notifications_queue', array(
        'retry_time' => (time() + 300),
        'to_email' => $this->to_email,
        'to_name' => $this->to_name,
        'from_email' => $this->from_email,
        'from_name' => $this->from_name,
        'cc' => $this->cc,
        'bcc' => $this->bcc,
        'content_type' => $this->content_type,
        'has_attachments' => $has_attachments,
        'subject' => $this->subject,
        'message' => $this->message)
    );

    // Deactivate SMTP server
    $value = redis::lrange('config:email_servers', $this->server_num, 1);
    $vars = json_decode($value, true);
    $vars['is_active'] = 0;
    redis::lset('config:email_servers', $this->server_num, json_encode($vars));

    // Return
    return true;

}


}

