<?php
declare(strict_types = 1);

namespace apex\app\msg\utils;

use apex\app;
use apex\svc\debug;
use apex\svc\redis;


/**
 * Handles various miscellaneous SMTP functions, such as obtaining the next 
 * rotating SMTP server connection in line. 
 */
class smtp_connections
{


    // Properties
    private $server_num;
    private $smtp_connections = [];


/**
 * Get the next SMTP server to send from 
 */
public function get_smtp_server()
{ 

    // Set variables
    $found = false;
    $retries = 0;
    $vars = array();

    // Get total number of servers in configuration
    $total_servers = redis::llen('config:email_servers');
    if ($total_servers < 1) { return false; }

    // Get active SMTP server
    do { 
        $this->server_num = app::get_counter('email_servers');
        if (!$value = redis::lindex('config:email_servers', $this->server_num)) { 
            redis::hset('counters', 'email_servers', -1);
            continue;
        }

        // Check number of retries
        if ($retries >= $total_servers) { 
            return false;
        }

        // Decode JSON, and check status
        $vars = json_decode($value, true);
        //if ($vars['is_active'] != 1) { 
            //$retries++;
            //continue;
        //}

        // Check for existing connection
        if (isset($this->smtp_connections[$vars['host']])) { 
            $this->write($this->smtp_connections[$vars['host']]['connection'], "RSET");
            return $this->smtp_connections[$vars['host']];
        }

        // Connect to SMTP
        if (!$smtp = $this->connect($vars['host'], (int) $vars['port'], $vars['username'], $vars['password'])) { 
            $vars['is_active'] = 0;
            redis::lset('config:email_servers', $num, json_encode($vars));
            $retries++;
            continue;
        }

        // Return
        return $smtp;

} while ($found === false);

}

/**
 * Connect to a SMTP server. 
 *
 * @param string $host The SMTP host
 * @param int $port The SMTP port
 * @param string $username The SMTP username
 * @param string $password The SMTP password
 * @param int $is_ssl A 1/0 defining whether or not to use SSL connection
 *
 * @return mixed The socket connection if successful, or false otherwise.
 */
private function connect(string $host, int $port, string $username, string $password, int $is_ssl = 0)
{ 

    // Set variables
    $orig_host = $host;
    if ($is_ssl == 1) { $host = 'ssl://' . $host; }

    // Connect
    if (!$sock = fsockopen($host, $port, $errno, $errstr, 5)) { 
        return false;
    }
    $response = fread($sock, 1024);

    // HELO
    $this->write($sock, "EHLO " . app::_config('core:domain_name'));

    // Authenticate
    if ($username != '' && $password != '') { 

        // Auth
        $this->write($sock, "AUTH LOGIN");
        $this->write($sock, base64_encode($username));
        $this->write($sock, base64_encode($password));
    }

    // Finish up
        $this->smtp_connections[$orig_host] = array(
        'connection' => $sock,
        'server_num' => $this->server_num,
        'host' => $orig_host,
    );

    // Return
    return $this->smtp_connections[$orig_host];

}

/**
 * Write line to a SMTP connection, and return the next line given by the SMTP 
 * server. 
 *
 * @param mixed $sock The SMTP socket connection
 * @param string $message The message to send to the SMTP server
 *
 * @return string The one-line response from the SMTP server
 */
public function write($sock, string $message)
{ 

    // Send message to SMTP
    fwrite($sock, "$message\r\n");
    $response = fread($sock, 1024);

    // Return
    return $response;

}


}

