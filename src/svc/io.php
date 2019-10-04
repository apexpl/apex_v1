<?php
declare(strict_types = 1);

namespace apex\svc;

use apex\app;
use apex\app\io\io as service;
use apex\app\exceptions\ServiceException;


/**
 * I/O file and directory service / dispatcher.  Object passed as the singleton must 
 * be of the class apex\app\io/io
 */
class io
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

