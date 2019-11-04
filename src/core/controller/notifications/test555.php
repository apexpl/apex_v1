<?php
declare(strict_types = 1);

namespace apex\core\controller\notifications;

use apex\app;
use apex\svc\db;
use apex\core\controller\notifications;
use apex\users\user;
use apex\core\admin;

/**
 * Abstract nofications controller, used as a template 
 * for the actual notification controller.
 */
class test555 extends notifications
{

    // Properties
    public $display_name = 'Controller Name';

    // Set fields
    public $fields = array(

    );

    // Senders
    public $senders = array(
        'admin' => 'Administrator',
        'user' => 'User'
    );

    // Recipients
    public $recipients = array(
        'user' => 'User',
        'admin' => 'Administrator'
    );

/**
 * Get available merge fields.  Used when creating notification via admin 
 * panel -- Settings->Notifications menu.  This helps populate the select list 
 * of available merge fields.
 *
 * @return array Associatve array of the available merge fields for this notification controller.
 */
public function get_merge_fields():array
{

    // Set fields
    $fields = array();

    // Return
    return $fields;

}
    


/**
 * Get merge variables.
 *
 * This obtains the necessary merge variables from the database, as you defined 
 * within the get_merge_fields() function of this class.  These are used to personalize the 
 * message for the specific transaction / request.
 *
 * @param int $userid The ID# of the user e-mails are being processed against.
 * @param array $data Any extra data needed.
 *
 * @return array An associative array of the merge variables to personalize the e-mail messgae with.
 */
public function get_merge_vars(int $userid, array $data):array
{

    // Set vars
    $vars = array();

    // Return
    return $vars;

}


/**
 * Get recipient
 *
 * Used when the notification controller supports recipients / senders other than the 
 * defaults of admin:XX and user.  This function takes in the string of the recipient / sender, plus the 
 * ID# of the user notifications are being processed against, and returns an array of the full name and e-mail  
 * address of recipient / sender.
 *
 * @param string $recipient The recipient to obtain.
 * @param int $userid The user id that e-mails are being processed against.
 * @param array $data Optional array of any necessary data. 
 */
public function get_recipient(string $recipient, int $userid, array $data = array())
{ 

    // Return
    return false;

}

}


