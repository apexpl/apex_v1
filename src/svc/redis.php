<?php
declare(strict_types = 1);

namespace apex\svc;

use apex\app\exceptions\ServiceException;
use redis as redisdb;

/**
 * Database service / dispatcher.  Object passed as the singleton must 
 * implement apex\app\interfaces\DBInterface 
 */
class redis
{

    private static $instance = null;


    /**
     * Sets / returns the instance for this service / dispatcher.
     */
    public static function singleton()
    {

        // Return, if already defined
        if (self::$instance !== null) { 
            return self::$instance;
        }

        // Check if connection info defined
        if (!defined('REDIS_HOST')) { 
            return false;
        }

        // Connect to redis
        self::$instance = new redisdb();
        if (!self::$instance->connect(REDIS_HOST, REDIS_PORT, 2)) { 
            throw new ApexException('emergency', "Unable to connect to redis database.  We're down!");
        }

        // Authenticate redis, if needed
        if (REDIS_PASS != '' && !self::$instance->auth(REDIS_PASS)) { 
            throw new ApexException('emergency', "Unable to authenticate redis connection.  We're down!");
        }

        // Select redis db, if needed
        if (REDIS_DBINDEX > 0) { 
            self::$instance->select(REDIS_DBINDEX);
        }

        // Return
        return self::$instance;

    }

    /**
     * Calls a method of the instance.
     */
    public static function __callstatic($method, $params) 
    {

        // Ensure we have an instance defined
        if (!self::$instance) { 
            self::singleton();
        }

        // Ensure method exists
        if (!method_exists(self::$instance, $method)) { 
            throw new ServiceException('no_method', __CLASS__, $method);
        }

        // Call method, and return 
        return self::$instance->$method(...$params);
    }

}

