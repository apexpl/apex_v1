<?php
declare(strict_types = 1);

namespace apex\app\exceptions;

use apex\app;
use apex\app\exceptions\ApexException;


/**
 * Handles all encryption errors 
 */
class EncryptException   extends ApexException
{

    // Properties
    private $error_codes = array(
        'unable_generate_rsa' => "Unable to generate new RSA key-pair for user ID# {userid}, user type: {user_type}",
        'no_encrypt_user' => "Unable to encrypt data key to the recipient: {recipient}",
        'no_private_key' => "Unable to decrypt private RSA key for user ID# {userid}, user type: {user_type}",
        'no_session_password' => "Unable to determine session decryption password.  It's not where it should be!",
        'no_data' => "No encrypted data exists with the ID# {data_id}",
        'no_gnupg' => "The php-gnupg PHP extension is not installed, and is required for all PGP based encryption operations",
        'invalid_pgp_key' => "Unable to add PGP key, as it is not a valid PGP key"
    );


/**
 * Construct 
 *
 * @param string $message The exception message.
 * @param string $userid The ID# of the user.
 * @param string $user_type The type of user (user / admin).
 * @param string $recipient The encryption recipient
 * @param string $data_id The ID# of the data record being accessed.
 */
public function __construct($message, $userid = 0, $user_type = '', $recipient = '', $data_id = 0)
{ 

    // Set variables
    $vars = array(
        'userid' => $userid,
        'user_type' => $user_type,
        'recipient' => $recipient,
        'data_id' => $data_id
    );

    // Set variables
    $this->is_generic = 1;
    $this->log_level = 'error';

    // Get message
    $this->message = $this->error_codes[$message] ?? $message;
    $this->message = tr($this->message, $vars);

}


}

