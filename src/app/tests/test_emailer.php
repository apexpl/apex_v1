<?php
declare(strict_types = 1);

namespace apex\app\tests;

use apex\app;
use apex\svc\db;
use apex\svc\redis;
use apex\svc\debug;
use apex\svc\msg;
use apex\app\msg\emailer;
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
class test_emailer extends emailer
{



    /**
     * @Inject
     * @var app
     */
    private $app;

    // Properties
    private $queue = [];


/**
 * Send an e-mail message. 
 *
 * @param EventMessageInterface $msg The e-mail message to send.
 */
public function dispatch(EmailMessageInterface $msg)
{ 

    // Add to queue
    $this->queue[] = $msg;

    // Return
    return true;

}

/**
 * Search the mail queue.
 *
 * This will search through the queue of e-mail messages that contain all 
 * e-mails sent during testing, find any that meet your search criteria, and return 
 * them in an array.  All search variables are optional.
 *
 * @param string $to_email The recipient e-mail address.
 * @param string $from_email The sender e-mail address
 * @param string $subject Text the subject will contain
 * @param string $message Text the message body will contain
 *
 * @return EventMessageInterface[] An array of messages that meet the search criteria.
 */
public function search_queue(string $to_email = '', string $from_email = '', string $subject = '', string $message = '')
{

    // Go through queue
    $results = array();
    foreach ($this->queue as $msg) { 

        // Search
        if ($to_email != '' && $msg->get_to_email() != $to_email) { continue; }
        if ($from_email != '' && $msg->get_from_email() != $from_email) { continue; }
        if ($subject != '' && !preg_match("/$subject/i", $msg->get_subject())) { continue; }
        if ($message != '' && !preg_match("/$message/i", $msg->get_message())) { continue; }

        // Add to results
        $results[] = $msg;
    }

    // Return
    return $results;

}

/**
 * Get the current queue of messages
 *
 * @return array The current queue of messages.
 */
public function get_queue() { return $this->queue; }

/**
 * Clear the queue
 */
public function clear_queue() { $this->queue = []; }




}

