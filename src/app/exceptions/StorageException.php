<?php
declare(strict_types = 1);

namespace apex\app\exceptions;

use apex\app;
use apex\app\exceptions\ApexException;


/**
 * Handles all exceptions for the remote storage library, which 
 * uses the league/flaystem package.
 */
class StorageException   extends ApexException
{

    // Properties
    private $error_codes = array(
        'no_adapter' => 'Unknown remote file system adapter, {type}', 
        'no_filesystem' => 'Unable to connect to remote file system with adapter {type}.  Please check the credentials.', 
        'no_file_add' => 'Unable to add file to remote file system at {dest}, using local file {file} via adapter {type}', 
        'no_file_add_contents' => 'Unable to add remote file with contents at {dest}',
        'no_read_file' => 'Unable to read file from remote filesystem at {dest}',  
        'no_delete_file' => 'Unable to delete file from remote filesystem, {dest}', 
        'no_rename_file' => 'Unable to rename file on remote filesystem from {file} to {dest}', 
        'no_copy_file' => 'Unable to copy file on remote filesystem from {file} to {dest}', 
        'no_open_file' => 'Unable to open local file at {file}', 
        'file_not_exists' => 'Unable to add file to remote file system, as local file does not exist at {file}', 
        'no_create_dir' => 'Unable to create directory on remote filesystem, {dest}',
        'no_delete_dir' => 'Unable to delete directory on remote filesystem, {dest}'  

    );

/**
 * Construct
 *
 * @param string $message The exception message
 * @param string $type The adapater type.
 * @param string $filename The local filename
 * @param string $dest The destination on the remote file system. 
 */
public function __construct($message, string $type = '', string $filename = '', string $dest = '')
{ 

    // Set variables
    $vars = array(
        'type' => $type, 
        'file' => $filename, 
        'dest' => $dest
    );

    // Get message
    $this->log_level = 'error';
    $this->message = $this->error_codes[$message] ?? $message;
    $this->message = tr($this->message, $vars);

}


}

