<?php
declare(strict_types = 1);

namespace apex\svc;

use apex\app;
use apex\app\interfaces\LoggerInterface as service;
use apex\app\exceptions\ServiceException;


/**
 * Logger service / dispatcher.  Object passed as the singleton must 
 * implement apex\app\interfaces\LoggerInterface 
 */
class log
{

    private static $class_name = service::class;
    private static $instance = null;


    /**
     * Calls a method of the instance.
     */
    public static function __callstatic($method, $params) 
    {

        // Ensure we have an instance defined
        if (!self::$instance) {
            self::$instance = app::get(self::$class_name);
        }

        // Ensure method exists
        if (!method_exists(self::$instance, $method)) { 
            throw new ServiceException('no_method', __CLASS__, $method);
        }

        // Call method, and return 
        return self::$instance->$method(...$params);
    }

}

