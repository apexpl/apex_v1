<?php
declare(strict_types = 1);

namespace apex\libc;

use apex\app\exceptions\ServiceException;
use redis as redisdb;
use RedisException;

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
        if (!getEnv('redis_host')) { 
            return false;
        }

        // Connect to redis
        self::$instance = new redisdb();
        try {
            self::$instance->connect(getEnv('redis_host'), (int) getEnv('redis_port'), 2);
        } catch (RedisException $e) { 
            echo "Unable to connect to redis.  We're down!";
            exit(0);
        }

        // Authenticate redis, if needed
        $password = getEnv('redis_password') ?? '';
        if ($password != '') { 

            try { 
                self::$instance->auth($password);
            } catch (RedisException $e) { 
                echo "Unable to authenticate into redis.  We're down!";
                exit(0);
            }
        }

        // Select redis db, if needed
    $dbindex = getEnv('redis_dbindex') ?? 0;
        if ((int) $dbindex > 0) { 
            self::$instance->select((int) $dbindex);
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

