<?php
declare(strict_types = 1);

namespace apex\app\exceptions;

use apex\app;
use apex\app\exceptions\ApexException;


/**
 * Handles all file I/O errors, such as file does not exist, directory does 
 * not exists, unable to create or unpack zip archive, etc. 
 */
class IOException extends ApexException
{



    // Properties
    private $error_codes = array(
    'zip_not_exists' => "Zip archive file does not exist at {file}",
    'zip_invalid' => "Not a valid zip archive at {file}",
    'no_open_dir' => "Unable to open the directory for reading, {file}",
    'no_mkdir' => "Unable to create the directory at, {file}",
    'no_unlink' => "Unable to delete the file at, {file}",
    'no_rmdir' => "Unable to delete the directory located at, {file}"
    );
/**
 * Construct 
 *
 * @param string $message The exception message.
 * @param string $file The filename / directory.
 */
public function __construct($message, $file = '')
{ 

    // Set variables
    $vars = array(
        'file' => $file
    );

    // Get message
    $this->log_level = 'error';
    $this->message = $this->error_codes[$message] ?? $message;
    $this->message = tr($this->message, $vars);

}


}

