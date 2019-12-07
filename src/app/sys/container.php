<?php
declare(strict_types = 1);

namespace apex\app\sys;

use apex\app;
use apex\app\exceptions\ContainerException;
use apex\app\msg\emailer;

/**
 * The dependency injection container.
 */
class container
{

    // Properties
    private static $services = [];
    private static $items = [];
    private static $use_declarations = [];

/**
 * Build container.
 *
 * Does the initial build of the container, gets the 
 * default services based on request type, and loads 
 * the necessary PHP classes.
 *
 * @param string $reqtype The type of request (http, cli, test, etc.)
 */
public function build_container(string $reqtype = 'http')
{

    // Empty items.
    self::$items = [];

    // Get services
    self::$services = require(SITE_PATH . '/bootstrap/' . $reqtype . '.php');

    // Set app class
    $app = app::get_instance();
    self::set(app::class, $app);

}

/**
 * Get an item from the container.
 *
 * @param string $name The name of the item to retrive.
 */
public static function get(string $name)
{

    // Check for item
    if (isset(self::$items[$name])) { 
        return self::$items[$name];

    } elseif (isset(self::$services[$name]) && isset(self::$items[self::$services[$name][0]])) { 
        return self::$items[self::$services[$name][0]];
    }

    // Check for variable
    if (isset(self::$services[$name]) && self::$services[$name][0] == 'var') { 
        self::$items[$name] = self::$services[$name][1] ?? '';
        return self::$items[$name];
    }

    // Get params
    if (isset(self::$services[$name]) && isset(self::$services[$name][1])) { 
        $params = self::$services[$name][1];
    } else { 
        $params = [];
    }

    // Try to make item
    $value = self::make($name, $params);

    // Set, and return
    self::set($name, $value);
    return $value;

}

/**
 * Check if container has item available.
 *
 * @param string $name The name of the item to check availablility of.
 *
 * @return bool Whether or not name is available.
 */
public static function has(string $name):bool
{

    // Get
    $ok = isset(self::$items[$name]) || class_exists($name) ? true : false;
    return $ok;

}

/** 
 * Set an item into the container
 *
 * @param string $name The name of the item.
 * @param mixed $value The value of the item.
 */
public static function set(string $name, &$value)
{

    // Set item
    self::$items[$name] = $value;

}

/**
 * Make a new object with dependency injection.
 *
 * @param string $name The class name to create.
 * @param array $params Any additional parameters to use within DI.
 */
public static function make(string $name, array $params = [])
{

    // Get class name
    if (!$class_name = self::get_class_name($name)) { 
            throw new ContainerException('no_class_name', $name);
    }

    // Load the class
    $object = new \ReflectionClass($class_name);

    // Get use statements
    self::$use_declarations[$class_name] = self::get_use_declarations($object->getFilename());

    // Get constructor
    if ($method = $object->getConstructor()) { 
        $injection_params = self::get_injection_params($method, $params);
    } else { 
        $injection_params = [];
    }

    // Make object
    $instance = $object->NewInstanceArgs($injection_params);

    // Go through properties for annotation based injection
    $props = $object->getProperties();
    foreach ($props as $prop) {
        if (!$doc = $prop->getDocComment()) { 
            continue;
        }

        if ($value = self::check_property_docblock($doc, $class_name)) { 
            $prop->setAccessible(true);
            $prop->setValue($instance, $value);
        }
    }

    // Return
    return $instance;

}

/**
 * Make and set item into container.
 *
 * @param string $class_name The class name of the object to make.
 * @param array $params Optional name based params to use during object creation.
 *
 * @return mixed The newly created object.
 */
public static function makeset(string $class_name, array $params = [])
{

    // Make the object
    $object = self::make($class_name, $params);

    // Set the item
    self::set($class_name, $object);

    // Return
    return $object;

}

/**
 * Call specific method within a class.
 *
 * @param array $callable First element is class name, second element the method name.
 * @param array $params User-defined params to use during injection.
 *
 * @return mixed The response from the called method.
 */
public static function call(array $callable, array $params = [])
{

    // Get object
    $object = self::get($callable[0]);
    $class_name = self::get_class_name($callable[0]);

    // Get method
    $tobject = new \ReflectionClass($class_name);
    $method = $tobject->getMethod($callable[1]);

    // Get injection params
    $injection_params = self::get_injection_params($method, $params);

    // Return
    return $method->invokeArgs($object, $injection_params);

}

/**
 * Get injection parameters fr a method.
 *
 * @param \ReflectionMethod $method The method to obtain injection parameters for.
 * @param array $params The user defined parameters to use for injection.
 *
 * @return mixed Array of parameters if successful, false otherwise (ie. unable to find dependency).
 */
private static function get_injection_params(\ReflectionMethod $method, array $params = [])
{

    // Initialize
    $injection_params = [];
    $method_params = $method->getParameters();

    // Go through params
    foreach ($method_params as $param) { 

        // Get param variables
    $name = $param->getName();
        $type = $param->hasType() === true ? $param->getType() : null;

        // Check params
        if (isset($params[$name])) {

            // Check type
            if (!self::check_type($params[$name], $type)) { 
                throw new ContainerException('invalid_param_type', $name, GetType($params[$name]), (string) $type->getName());
            }

            // Add to injected params
            $injection_params[$name] = $params[$name];
            continue;
        }

        // Try to get param from container
    if ($type !== null) { $type = $type->getName(); }
        if ($type !== null && strpos((string) $type, "\\") && $value = self::get((string) $type)) { 
            $injection_params[$name] = $value;

        } elseif ($param->isDefaultValueAvailable() === true) { 
            $injection_params[$name] = $param->getDefaultValue();

        } elseif ($param->isOptional() === true) { 
            $injection_params[$name] = null;

        } else { 
            throw new ContainerException('no_param_found', $name, (string) $type);
        }

    }

    // Return
    return $injection_params;

}

/**
 * Check doc comment from property.
 *
 * Checks for the @inject tag, and if found, injects with the 
 * value of the @var tag.
 *
 * @param string $comment The dov comment of the properlty.
 * @param string $class_name The class name of the  property.
 */
public static function check_property_docblock(string $comment, string $class_name)
{

    // Initialize
    $is_inject = false;
    $var_name = '';
    $use = self::$use_declarations[$class_name] ?? [];
    $lines = explode("\n", $comment);

    // Go through lines
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line == '' || $line == "/**' || $line == '*/") { continue; }
        if (preg_match("/\@inject/i", $line)) { 
            $is_inject = true;
        } elseif (preg_match("/\@var (.+)/", $line, $match)) { 
            $var_name = trim($match[1]);
        }
    }

    // Inject, if needed
    if ($is_inject === true && isset($use[$var_name]) && class_exists($use[$var_name])) {
        $name = $use[$var_name];
        if (isset(self::$services[$name])) { $name = self::$services[$name][0]; }

        return self::get($name);
    }

    // Return
    return false;

}









/**
 * Check variable type.
 *
 * @param mixed $value The value to check.
 * @param string $chk_type The type of variabke ti check variable for.
 *
 * @return bool Whether or not the value is of the specified type.
 */
public static function check_type($value, $chk_type):bool 
{

    // Initialize
    if ($chk_type !== null) { $chk_type = $chk_type->getname(); }

    // Return, if no check type
    if ($chk_type === null || $chk_type == '') { 
        return true;
    }

    // Check type
    if ($chk_type == 'int') { 
        $ok = is_integer($value) ? true : false;
    } elseif ($chk_type == 'string') { 
        $ok = is_string($value) ? true : false;
    } elseif ($chk_type == 'float') { 
        $ok = is_float($value) ? true : false;
    } elseif ($chk_type == 'bool') { 
        $ok = is_bool($value) ? true : false;
    } elseif ($chk_type == 'array') { 
        $ok = is_array($value) ? true : false;
    } elseif ($chk_type == 'object') { 
        $ok = is_object($value) ? true : false;
    } elseif (is_object($value)) { 
        $chk_classes = array_values(class_implements($value));
        $chk_classes[] = get_class($value);
        $ok = in_array($chk_type, $chk_classes) ? true : false;

    } else { 
        $ok = $value instanceof $chk_type ? true : false;
    }

    // Return
    return $ok;

}

/**
 * Get use declarations of PHP class.
 *
 * @param string $filename The filename of the class.
 */
public static function get_use_declarations(string $filename)
{

    if (!file_exists($filename)) { return []; }

    // Initialize
    $in_namespace = false;
    $declarations = [];
    $lines = file($filename);

    // Go through lines
    foreach ($lines as $line) { 
        if (preg_match("/^namespace /", $line)) { 
            $in_namespace = true;
        } elseif (preg_match("/^(class |interface |function )/", $line)) { 
            break;
        } elseif ($in_namespace === true && preg_match("/^use (.*?)\;/i", $line, $match)) { 
            $elements = explode("\\", $match[1]);
            $short_name = array_pop($elements);
            $declarations[$short_name] = $match[1];
        }
    }

    // Return
    return $declarations;

}

/**
 * Get class name
 *
 * @param string $name The class name to check, and retrive full class name of.
 * 
 * @return mixed Either the resulting class name, or flase if not found.
 */
private static function get_class_name(string $name)
{

    // Check services
    if (isset(self::$services[$name])) { 
        return self::$services[$name][0];

    } elseif (class_exists($name)) { 
        return $name;
    } else { 
        return false;
    }

}


}


