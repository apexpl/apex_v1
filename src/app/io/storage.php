<?php
declare(strict_types = 1);

namespace apex\app\io;

use apex\app;
use apex\svc\db;
use apex\svc\redis;
use apex\svc\debug;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Sftp\SftpAdapter;
use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use Spatie\Dropbox\Client;
use Spatie\FlysystemDropbox\DropboxAdapter;
use apex\app\exceptions\StorageException;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;


/**
 * Remote File Storage Handler
 *
 * Service: apex\svc\storage
 *
 * Handles the management of file storage via the remote/flysystem package, 
 * allowing for files to be distributed amongst multiple servers / services such as AWS.
 *
 * This class is available within the services container, meaning its methods can be accessed statically via the 
 * service singleton as shown below.
 *
 * PHP Example
 * --------------------------------------------------
 * 
 * <?php
 * 
 * namespace apex;
 * 
 * use apex\app;
 * use apex\svc\storage;
 *
 (
 ( // Set cache item
 * cache::set('some_id', 'my item contents');
 *
 * // Get cache item
 * $data = cache::get('some_id');
 *
 */
class storage
{

    // Properties
    private $fs;
    private $adapter_type;

/**
 * Constructor.  Create the adapter.
 *
 * @param string $adapter_type The type of adapter to use.  If blank, uses the default config var 'core:flysystem_type'.
 * @param array $$credentials Optional credentials to use for the adapter. 
 */
public function __construct(string $adapter_type = '', array $credentials = [])
{

    // Set default adapter, if needed
    if ($adapter_type == '') { 
        $adapter_type = app::_config('core:flysystem_type');
    }
    $this->adapter_type = $adapter_type;
    $case_sensitive = true;

    // Get default credentials, if needed
    if (count($credentials) == 0) { 
        $credentials = json_decode(app::_config('core:flysystem_credentials'), true);
    }

    // Local adapter
    if ($adapter_type == 'local') { 

        $adapter = new Local(
            SITE_PATH . '/storage/', 
            LOCK_EX, 
            Local::DISALLOW_LINKS,
            [
                'file' => [
                    'public' => 0744,
                    'private' => 0700
                ], 
                'dir' => [
                    'public' => 0755,
                    'private' => 0700
                ]
            ]
        );

    // sFTP adapter
    } elseif ($adapter_type == 'sftp') {
        $adapter = new SftpAdapter([
            'host' => $credentials['sftp_host'], 
            'username' => $credentials['sftp_username'], 
            'password' => $credentials['sftp_password'], 
            'port' => $credentials['sftp_port'], 
            'timeout' => 10, 
            'root' => $credentials['sftp_root'], 
            'private_key' => $credentials['sftp_private_key']
        ]);

    // AWS3
    } elseif ($adapter_type == 'aws3' || $adapter_type == 'digitalocean') { 

        // Set connection vars
        $connect_vars = array(
            'credentials' => [
                'key' => $credentials['aws3_key'], 
                'secret' => $credentials['aws3_secret']
            ], 
            'region' => $credentials['aws3_region'], 
            'version' => $credentials['aws3_version']
        );

        // Add endpoint, if DigialOcean
        if ($adapter_type == 'digitalocean') { 
        $connect_vars['endpoint'] = 'https://' . $credentials['aws3_region'] . '.digitaloceanspaces.com';
    }

        // Get adapter
        $client = new S3Client($connect_vars);
        $adapter = new AwsS3Adapter($client, $credentials['aws3_bucket_name'], $credentials['aws3_prefix']);

    // DropBox
    } elseif ($adapter_type == 'dropbox') { 
        $client = new Client($credentials['dropbox_auth_token']);
        $adapter = new DropboxAdapter($client);
        $case_sensitive = false;
    // Throw exception
    } else { 
        throw new StorageException('no_adapter', $adapter_type);
    }

    // Create filesystem
    if (!$this->fs = new Filesystem($adapter, ['case_sensitive' => $case_sensitive])) { 
        throw new StorageException('no_filesystem', $adapter_type);
    }

}

/**
 * Add file
 *
 * Checks the filesize, and if greater than 1MB will stream it, otherwise 
 * will do a straight write file method.
 *
 * @param string $dest The path of the file destination
 * @param string $filename The full path of the local file to add.
 * @param string $visibility Mode of the file, either 'public' or 'private'.
 * @param bool $can_overwrite Whether or not the file can be overwritten if already exists.  Defaults to true.
 *
 * @return bool Whther or not the operation was successful.
 */
public function add(string $dest, string $filename, string $visibility = 'public', bool $can_overwrite = true):bool
{

    // Debug
    debug::add(2, tr("Adding file to storage, local: {1}, dest: {2}, visibility: {3}", $filename, $dest, $visibility));

    // Check file
    if (!file_exists($filename)) { 
        throw new StorageException('file_not_exists', $this->adapter_type, $filename, $dest);
    }

    // Stream file, if greater than 1MB
    if (filesize($filename) > 1048576) { 
        $func_name = $can_overwrite === true ? 'putStream' : 'writeStream';

        // Open file
        if (!$fh = fopen($filename, 'rb')) { 
            throw new StorageException('no_file_open', $this->adapter_type, $filename, $dest);
        }

        // Write the file
        try {
            $response = $this->fs->$func_name($dest, $fh, ['visibility' => $visibility]);
        } catch (FileExistsException $e) {
            throw new StorageException('no_add_file', $this->adapter_type, $filename, $dest);
        }
        fclose($fh);

    // Write standard file
    } else { 
        $func_name = $can_overwrite === true ? 'put' : 'write';

        try {
            $response = $this->fs->$func_name($dest, file_get_contents($filename), ['visibility' => $visibility]);
        } catch (FileExistsException $e) {
            throw new StorageException('no_add_file', $this->adapter_type, $filename, $dest);
        }

    }

    // Return
    return $response;

}

/**
 * Add file from contents
 *
 * @param string $dest The path of the file destination
 * @param string $contents The contents of the file to add
 * @param string $visibility Mode of the file, either 'public' or 'private'.
 * @param bool $can_overwrite Whether or not the file can be overwritten if already exists.  Defaults to true.
 *
 * @return bool Whther or not the operation was successful.
 */
public function add_contents(string $dest, string $contents, string $visibility = 'public', bool $can_overwrite = true):bool
{

    // Debug
    debug::add(3, tr("Adding file to remote system via contents at {1}", $dest));

    // Write the file
    $func_name = $can_overwrite === true ? 'put' : 'write';
    try {
        $response = $this->fs->$func_name($dest, $contents, ['visibility' => $visibility]);
    } catch (FileExistsException $e) {
        throw new StorageException('no_add_file_contents', $this->adapter_type, '', $dest);
    }

    // Return
    return $response;

}

/**
 * Check if file exists
 *
 * @param string $file The file to check whether or not it exists.
 *
 * @return bool Whether or not the file exists.
 */
public function has(string $file):bool
{

    // Debug
    debug::add(4, tr("Checking if file exists in remote file system, {1}", $file));

    // Check
    $response = $this->fs->has($file);

    // Return
    return $response;

}

/**
 * Get a file from the remote file system.
 * 
 * @param string $file Path to the file on remote system to retrieve.
 *
 * @return string The contents of the file.
 */
public function get(string $file)
{

    // Debug
    debug::add(3, tr("Getting file from remote filesystem, {1}", $file));

    // Get the file
    try {
        $response = $this->fs->read($file);
    } catch (FileNotFoundException $e) { 
        throw new StorageException('no_read_file', $this->adapter_type, '', $file);
    }

    // Return
    return $response;

}

/**
 * Get file stream from remote filesystem.
 *
 * @param string $file The file path on the remote server to retrieve.
 *
 * @return resource The stream resource of the file.
 */
public function get_stream(string $file)
{

    // Debug
    debug::add(3, tr("Getting stream of file from remote filesystem at {1}", $file));

    // Get the file stream
    try {
        $sock = $this->fs->readStream($file);
    } catch (FileNotFoundException $e) { 
        throw new StorageException('no_read_file', $this->adapter_type, '', $file);
    }

    // Return
    return $sock;

}

/**
 * Delete file
 *
 * @param string $file The file path to delete from remote filesystem.
 *
 * @return bool Whether or not the operation was successful.
 */
public function delete(string $file):bool
{

    // Debug
    debug::add(3, tr("Delete file off remote filesystem {1}", $file));

    // Delete file
    try {
        $response = $this->fs->delete($file);
    } catch (FileNotFoundException $e) { 
        throw new StorageException('no_delete_file', $this->adapter_type, '', $file);
    }

    // Return
    return $response;

}

/**
 * Rename a file
 *
 * @param string $from The from file path.
 * @param string $dest The destination file path.
 *
 * @return bool Whther or not the operation was successful.
 */
public function rename(string $from, string $dest):bool
{

    // Debug
    debug::add(3, tr("Renaming remote file {1} to {2}", $from, $dest));

    // Rename file
    try {
        $response = $this->fs->rename($from, $dest);
    } catch (FileNotFoundException $e) { 
        throw new StorageException('no_rename_file', $this->adapter_type, $from, $dest);
    }

    // Return
    return $response;

}

/**
 * Copy a file
 *
 * @param string $from The from file path.
 * @param string $dest The destination file path.
 *
 * @return bool Whther or not the operation was successful.
 */
public function copy(string $from, string $dest):bool
{

    // Debug
    debug::add(3, tr("Copying remote file {1} to {2}", $from, $dest));

// Copy file
    try {
        $response = $this->fs->copy($from, $dest);
    } catch (FileNotFoundException $e) { 
        throw new StorageException('no_copy_file', $this->adapter_type, $from, $dest);
    }

    // Return
    return $response;

}

/**
 * Create a directory
 * 
 * @param string $dir Path of directory to create.
 *
 * @return bool Whether or not the operation was successful.
 */
public function create_dir(string $dir):bool
{

    // Debug
    debug::add(3, tr("Creating directory on remote filesystem at {1}", $dir));

    // Create dir
    if (!$response = $this->fs->createdir($dir)) { 
        throw new StorageException('no_create_dir', $this->adapter_type, $dir);
    }

    // Return
    return $response;

}

/**
 * Create a directory
 * 
 * @param string $dir Path of directory to create.
 *
 * @return bool Whether or not the operation was successful.
 */
public function delete_dir(string $dir):bool
{

    // Debug
    debug::add(3, tr("Deleting directory on remote filesystem at {1}", $dir));

    // Delete dir
    if (!$response = $this->fs->deletedir($dir)) { 
        throw new StorageException('no_delete_dir', $this->adapter_type, $dir);
    }

    // Return
    return $response;

}

/**
 * Get mime type of file
 *
 * @param string $file Parth to the file on the remote filesystem.
 *
 * @return mixed The mime type, or false on failure.
 */
public function get_mime_type(string $file)
{

    // Debug
    debug::add(3, tr("Getting MIM type of file on remote filesystem, {1}", $file));

    // Send request, and return
    $response = $this->fs->getMimetype($file);
    return $response;

}

/**
 * Get timestamps of file
 *
 * @param string $file Parth to the file on the remote filesystem.
 *
 * @return mixed The last modified time, or false on failure.
 */
public function get_timestamp(string $file)
{

    // Debug
    debug::add(3, tr("Getting last modified time of file on remote filesystem, {1}", $file));

    // Send request, and return
    $response = $this->fs->getTimestamp($file);
    return $response;

}

/**
Get size of file
 *
 * @param string $file Parth to the file on the remote filesystem.
 *
 * @return mixed The file size, or false on failure.
 */
public function get_size(string $file)
{

    // Debug
    debug::add(3, tr("Getting file size of file on remote filesystem, {1}", $file));

    // Send request, and return
    $response = $this->fs->getSize($file);
    return $response;

}

}


