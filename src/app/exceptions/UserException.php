<?php
declare(strict_types = 1);

namespace apex\app\exceptions;

use apex\app;
use apex\app\exceptions\ApexException;


/**
 * Handles all user exceptions, such as user does not exist, unable to create 
 * / update / delete user, etc. 
 */
class UserException   extends ApexException
{



    // Properties
    private $error_codes = array(
        'not_exists' => "No user exists within the system with the ID# {id}", 
        'no_username' => "No user exists within the system with the username {username}"
    );
/**
 * Construct 
 *
 * @param string $message The exception message.
 * @param string $userid The ID# of the user.
 */
public function __construct($message, $userid = 0, $username = '')
{ 

    // Set variables
    $vars = array(
        'id' => $userid, 
        'username' => $username
    );

    // Get message
    $this->log_level = 'error';
    $this->code = 500;
    $this->message = $this->error_codes[$message] ?? $message;
    $this->message = tr($this->message, $vars);

}


}

