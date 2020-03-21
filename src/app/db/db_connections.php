<?php
declare(strict_types = 1);

namespace apex\app\db;

use apex\app;
use apex\libc\db;
use apex\libc\redis;
use apex\app\db\mysql;


/**
 * Handles the various database connections, and whether they are read-only or 
 * write connections.  Retrives the necessary db connection information from 
 * redis when necessary. 
 */
class db_connections
{


    // Injected properties
    private $app;
    private $db;

    // Class properties
    private $connections = [];

/**
 * Check database connection 
 *
 * Get database connection.
 *
 * Checks to see whether we're connected to the mySQL database, and if so, if 
 * we're connected with the correct mySQL user depending on if it's a read / 
 * write connection. 
 *
 * @param string $type The type of connection we need (read or write).
 */
protected function get_connection(string $type = 'read')
{ 

    // Return if connected
    if (isset($this->connections[$type])) { 
        return $this->connections[$type];
    }

    // Get connection info
    $vars = $this->get_server_info($type);

    // Connect
    $conn = db::connect($vars['dbname'], $vars['dbuser'], $vars['dbpass'], $vars['dbhost'], (int) $vars['dbport']);
    $this->connections[$type] = $conn;

    // Return
    return $conn;

}

/**
 * Get a database server credentials. 
 *
 * @param string $type The type of connection.  Must be 'read' or 'write'
 */
private function get_server_info(string $type = 'read')
{ 

    // Initialize
    $total_slaves = redis::llen('config:db_slaves') ?? 0;

    // Get master server, if needed
    if ($type == 'write' || $total_slaves == 0) { 
        $vars = redis::hgetall('config:db_master');
    // Check read-only info
        if ($type == 'read' && $vars['dbuser_readonly'] != '') { 
            $vars['dbuser'] = $vars['dbuser_readonly'];
            $vars['dbpass'] = $vars['dbpass_readonly'];
        }

        // Return
        return $vars;
    }

    // Get slave server
    $num = app::get_counter('db_server');
    if (!$vars = redis::lindex('config:db_slaves', $num)) { 
        redis::hset('counters', 'db_server', 0);
        $vars = redis::lindex('config:db_slaves', 0);
    }

    // Return
    return json_decode($vars, true);

}


}

