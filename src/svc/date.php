<?php
declare(strict_types = 1);

namespace apex\svc;

use apex\app;
use apex\app\utils\date as service;
use apex\app\exceptions\ServiceException;


/**
 * Date service / dispatcher.  Object passed as the singleton must 
 * be of the class apex\app\utils\date
 */
class date
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

