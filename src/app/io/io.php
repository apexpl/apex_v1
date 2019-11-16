<?php
declare(strict_types = 1);

namespace apex\app\io;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\app\exceptions\IOException;
use apex\app\io\SqlParser;
use ZipArchive;
use CurlFile;

/**
 * I/O Library for File and Directory Handling.
 *
 * Service:  apex\svc\io
 * 
 * This class contains various methods to aid in managing files and directories, plus allows 
 * for the sending of HTTP requests, and creation / unpacking of zip archives.
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
 * use apex\svc\io;
 *
 * // Create a directory
 * io::create_dir($some_directory);
 *
 * // Parse directory
 * $files = io::parse_dir($some_dir);
 *
 */
class io
{

/**
 * Parse a directory recursively, and return all files and/or directories. 
 *
 * @param string $rootdir The directory name / path to parse.
 * @param bool $return_dirs Whether or not to return directory names, or only filenames.
 *
 * @return array An array of all resulting file / directory names.
 */
public function parse_dir(string $rootdir, bool $return_dirs = false)
{ 

    // Debug
    debug::add(5, tr("Parsing the directory, {1}", $rootdir));

    // Set variables
    $search_dirs = array('');
    $results = array();

    // Go through directories
    while ($search_dirs) { 
        $dir = array_shift($search_dirs);

        // Add director, if needed
        if ($return_dirs === true && !empty($dir)) { $results[] = $dir; }

        // Open, and search directory
        if (!$handle = opendir("$rootdir/$dir")) { 
        throw new IOException('no_open_dir', "$rootdir/$dir");
    }
        while (false !== ($file = readdir($handle))) { 
            if ($file == '.' || $file == '..') { continue; }

            // Parse file / directory
            if (is_dir("$rootdir/$dir/$file")) { 
                if (empty($dir)) { $search_dirs[] = $file; }
                else { $search_dirs[] = "$dir/$file"; }
            } else { 
                if (empty($dir)) { $results[] = $file; }
                else { $results[] = "$dir/$file"; }
            }
        }
        closedir($handle);
    }

    // Return
    return $results;

}

/**
 * Createa new directory recursively 
 *
 * Creates a directory recursively.  Goes through the parent directories, and 
 * creates them as necessary if they do not exist. 
 *
 * @param string $dirname The directory to create.
 */
public function create_dir(string $dirname)
{ 

    // Debug
    debug::add(4, tr("Creating new directory at {1}", $dirname));

    if (is_dir($dirname)) { return; }
    $tmp = str_replace("/", "\\/", sys_get_temp_dir());

    // Format dirname
    if (!preg_match("/^$tmp/", $dirname)) { 
        $dirname = trim(str_replace(SITE_PATH, "", $dirname), '/');
        $site_path = SITE_PATH;
    } else { 
        $site_path = sys_get_temp_dir();
        $dirname = preg_replace("/^$tmp/", "", $dirname);
    }
    $dirs = explode("/", $dirname);

    // Go through dirs
    $tmp_dir = '';
    foreach ($dirs as $dir) { 
        if ($dir == '') { continue; }
        $tmp_dir .= '/' . $dir;
        if (is_dir($site_path . '/' . $tmp_dir)) { continue; }

        // Create directory
        try { 
            @mkdir($site_path . '/' . $tmp_dir);
        } catch (Exception $e) { 
            throw new IOException('no_mkdir', $tmp_dir);
        }
    }

    // Return
    return true;

}

/**
 * Remove a directory 
 *
 * Removes a directory recursively.  Goes through all files and 
 * sub-directories, and deletes them before deleting the parent directory. 
 *
 * @param string $dirname The directory name to delete.
 */
public function remove_dir(string $dirname)
{ 

    // Debug
    debug::add(4, tr("Removing the directory at {1}", $dirname));

    if (!is_dir($dirname)) { return true; }
    $tmp = str_replace("/", "\\/", sys_get_temp_dir());

    // Parse dir
    if (!preg_match("/^$tmp/", $dirname)) { 
        $dirname = trim(str_replace(SITE_PATH, "", $dirname), '/');
        $files = $this->parse_dir(SITE_PATH . '/' . $dirname, true);
        $site_path = SITE_PATH . '/';
    } else { 
        $files = $this->parse_dir($dirname, true);        $site_path = sys_get_temp_dir();
        $dirname = preg_replace("/^$tmp/", "", $dirname);
    }

    // Go through, and delete all files
    foreach ($files as $file) { 
        if (is_dir($site_path . "$dirname/$file")) { continue; }

        try { 
            unlink($site_path . "$dirname/$file");
        } catch (Exception $e) { 
            throw new IOException('no_unlink', "$dirname/$file");
        
    }}

    // Delete directories
    $files = array_reverse($files);
    foreach ($files as $subdir) { 
        if (!is_dir($site_path . "$dirname/$subdir")) { continue; }

        try { 
            rmdir($site_path . "$dirname/$subdir");
        } catch (Exception $e) { 
            throw new IOException('normdir', "$dirname/$subdir");
        }
    }

    // Remove directory
    try { 
    rmdir($site_path . $dirname);
    } catch (Exception $e) { 
        throw new IOException('no_rmdir', $dirname);
    }

    // Return
    return true;

}

/**
 * Send a remote HTTP request 
 *
 * @param string $url The full URL to send hte HTTP request to.
 * @param string $method The method (GET/POST usually) of the request.  Defaults to GET.
 * @param array $request The request contents to send in array format.
 * @param string $content_type THe content type of the request.  Generally not needed, as the default works.
 * @param int $return_headers A 1 or 0 definine whether or not to return the HTTP readers of the response.
 *
 * @return string Returns the response from the server.
 */
public function send_http_request(string $url, string $method = 'GET', $request = array(), string $content_type = 'application/x-www-form-urlencoded', int $return_headers = 0)
{ 

    // Debug
    debug::add(2, tr("Sending HTTP request to the URL: {1}", $url));

    // Send via cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    //curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HEADER, $return_headers);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
    if (preg_match("/^https/", $url)) { curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); }

    // Set headers
    $headers = array('Content-type' => $content_type);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Set POST fields, if needed
    if ($method == 'POST') { 
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
    }

    // Send http request
    $response = curl_exec($ch);

    curl_close($ch);

    // Return
    return $response;

}

/**
 * / Send a remote HTTP request via the Tor network 
 *
 * @param string $url The full URL to send hte HTTP request to.
 * @param string $method The method (GET/POST usually) of the request.  Defaults to GET.
 * @param array $request The request contents to send in array format.
 * @param string $content_type THe content type of the request.  Generally not needed, as the default works.
 * @param int $return_headers A 1 or 0 definine whether or not to return the HTTP readers of the response.
 *
 * @return string Returns the response from the server.
 */
public function send_tor_request(string $url, string $method = 'GET', array $request = array(), string $content_type = 'application/x-www-form-urlencoded', int $return_headers = 0)
{ 

    // Debug
    //debug::add(3, tr("Sending HTTP request ia Tor to the URL: {1}"< $url));

    // Send via cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_PROXY, '127.0.0.1');
    curl_setopt($ch, CURLOPT_PROXYPORT, 9050);
    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    //curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HEADER, $return_headers);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
    if (preg_match("/^https/", $url)) { curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); }

    // Set POST fields, if needed
    if ($method == 'POST') { 
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
    }

    // Send http request
    $response = curl_exec($ch);
    curl_close($ch);

    // Return
    return $response;

}

/**
 * Download a remote file
 *
 * Downloads a file from the given URL, and stories it within the specifiled filename.  This 
 * streams the file into its target location to ensure there are no memory issues with larger files.
 *
 * @param string $url The URL of the remote file to download.
 * @param string $filename The filename to save the downloaded file to.
 *
 * @return bool WHther or not the operation was successful.
 */
public function download_file(string $url, string $filename):bool
{

    // Debug
    debug::add(3, tr("Downloading remote file from {1} and saving to {2}", $url, $filename));

    // Open file
    if (!$fh = fopen($filename, 'w+')) { 
        throw new IOException('file_no_write', $filename);
    }

    // Send via cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 600);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
    if (preg_match("/^https/", $url)) { curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); }
    curl_setopt($ch, CURLOPT_FILE, $fh); 

    // Send http request
    curl_exec($ch);

    // Close
    curl_close($ch);
    fclose($fh);

    // Debug
    debug::add(3, tr("Finished downloading file from {1}", $url));

    // Return
    return true;


}



/**
 * Generate a random string. 
 *
 * @param int $length The length of the random string.
 * @param bool $include_chars Whether or not to include special characters.
 *
 * @return string The generated random string.
 */
public function generate_random_string(int $length = 6, bool $include_chars = false):string
{ 

    // Debug
    debug::add(5, tr("Generating random string of length {1}", $length));

    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    if ($include_chars === true) { $characters = '!@#$%^&*()_-+=' . $characters . '!@#$%^&*()_-+='; }
    
    // Generate random string
    $string = '';
    for ($x = 1; $x <= $length; $x++) { 
        $num = sprintf("%0d", rand(1, strlen($characters) - 1));
        $string .= $characters[$num];
    }
    
    // Return
    return $string;

}

/**
 * Execute SQL file 
 *
 * @param string $sqlfile The path to the SQL file to execute against the database
 */
public function execute_sqlfile(string $sqlfile)
{ 

    // Check if SQL file exists
    if (!file_exists($sqlfile)) { 
        return;
    }

    // Debug
    debug::add(4, tr("Starting to execute SQL file, {1}", $sqlfile));

    // Execute SQL
    $sql_lines = SqlParser::parse(file_get_contents($sqlfile));
    foreach ($sql_lines as $sql) { 
        db::query($sql);
    }

    // Debug
    debug::add(2, tr("Successfully executed SQL file against database, {1}", $sqlfile), 'info');

}

/**
 * Creates a new zip archive from the given directory name. 
 *
 * @param string $tmp_dir The directory to archive
 * @param string $archive_file The location of the resulting archive file.
 */
public function create_zip_archive(string $tmp_dir, string $archive_file)
{ 

    // Debug
    debug::add(2, tr("Creating a new zip archive from directory {1} and aving at {2}", $tmp_dir, $archive_file));
 
    if (file_exists($archive_file)) { @unlink($archive_file); }
    $zip = new ZipArchive();
    $zip->open($archive_file, ZIPARCHIVE::CREATE);

    // Go through files
    $files = self::parse_dir($tmp_dir, true);
    foreach ($files as $file) { 
        if (is_dir($tmp_dir . '/' . $file)) { 
            $zip->addEmptyDir($file);
        } else { 
            $zip->addFile($tmp_dir . '/' . $file, $file);
        }
    }
    $zip->close();

    // Return
    return true;

}

/**
 * Unpack a zip archive 
 *
 * @param string $zip_file The path to the .zip archive
 * @param string $dirname The directory to create and unpack the archive to
 *
 * @return bool Whether or not the operation was successful.
 */
public function unpack_zip_archive(string $zip_file, string $dirname)
{ 

    // Debug
    debug::add(2, tr("Unpacking zip archive {1} into the directory {2}", $zip_file, $dirname));

    // Ensure archive file exists
    if (!file_exists($zip_file)) { 
        throw new IOException('zip_not_exists', $zip_file);
    }

    // Create directory to unpack to
    if (is_dir($dirname)) { $this->remove_dir($dirname); }
    $this->create_dir($dirname);

    // Open zip file
    if (!$zip = zip_open($zip_file)) { 
        throw new IOException('zip_invalid', $zip_file);
    }

    // Unzip package file
    while ($file = zip_read($zip)) { 

        // Format filename
        $filename = zip_entry_name($file);
        $filename = str_replace("\\", "/", $filename);
        if ($filename == '') { continue; }

        // Get contents
        $contents = '';
        while ($line = zip_entry_read($file)) { $contents .= $line; }
        if ($contents == '') { continue; }

        // Debug
        debug::add(5, tr("Unpacking file from zip archive, {1}", $filename));

        // Save file
        $this->create_dir(dirname("$dirname/$filename"));
        file_put_contents("$dirname/$filename", $contents);
    }
    zip_close($zip);

    // Debug
    debug::add(2, tr("Successfully unpacked zip archive {1} to directory {2}", $zip_file, $dirname));

    // Return
    return true;

}
/**
 * Send a chunked file
 * 
 * @parma string $url The URL to send the file to.
 * @param string $filename The full path of the file to send.
 * @param string $remote_filename The remote filename to send with the request
 */
public function send_chunked_file(string $url, string $filename, string $remote_filename)
{

    // Ensure file exists
    if (!file_exists($filename)) { 
        throw new IOException('file_not_exists', $filename);
    }

    // Get size of file
    $size = filesize($filename);
    $total_chunks = ceil($size / 524288);

    // Get URL
    $url = rtrim($url, '/') . '/upload_chunk/' . $remote_filename . '/';

    // Open file
    if (!$fh = fopen($filename, 'rb')) { 
        throw new IOException('file_no_read', $filename);
    }

    // Set variables
    $count = 0;
    $chunk_num = 1;
    $contents = '';

    // Go through file
    while ($buffer = fread($fh, 1024)) { 
        $contents .= $buffer;
        $count++;

        // Send request, if needed
        if ($count >= 512) { 

            // Set request
            $request = array(
                'contents' => base64_encode($contents)
            );

            // Send http request
            $response = $this->send_http_request($url . $chunk_num . '/' . $total_chunks, 'POST', $request);

            // Update variables, as needed
                $contents = '';
                $count = 0;
                $chunk_num++;
        }

    }

    // Send last chunk, if needed
    if (!empty($contents)) {
        $request = array('contents' => base64_encode($contents));
        $this->send_http_request($url . $chunk_num . '/' . $total_chunks, 'POST', $request);
    }

    // Return
    return true;


}


}

