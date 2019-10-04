<?php
declare(strict_types = 1);

namespace apex\app\sys;

use apex\app;
use apex\svc\debug;
use apex\svc\redis;
use apex\app\exceptions\ComponentException;


/**
 * Components Library
 *
 * Service: apex\svc\components
 *
 * Handles all internal components, such as checking whether or not a 
 * component exists, loading a component via the dependency injection 
 * container, obtaining the tpl/php file locations of a component, etc. 
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
 * use apex\svc\components;
 *
 * // Load a component
 * $client = components::load('worker', 'myworker', 'somepackage');
 *
 */
class components
{


/**
 * Check if component exists 
 *
 * Takes in the component type and alias, formatted in standard Apex format 
 * (PACKAGE:[PARENT:]ALIAS), checks to see if it exists, and returns an array 
 * of the package, parent and alias. 
 *
 * @param string $type The component type (eg. htmlfunc, lib, modal, etc.)
 * @param string $alias Apex formatted component alias (PACAKGE:[PARENT:]ALIAS)
 *
 * @return mixed/array Returns false if no components exists, otherwise returns array of the package, parent and alias.
 */
public function check(string $type, string $alias)
{ 

    // Initialize
    $parts = explode(":", strtolower($alias));
    if (count($parts) == 3) { 
        list($package, $parent, $alias) = array($parts[0], $parts[1], $parts[2]);
    } elseif (count($parts) == 2) { 
        list($package, $parent, $alias) = array($parts[0], '', $parts[1]);
    } else { 
        list($package, $parent) = array('', '');
    }

    // Debug
    debug::add(5, tr("Checking if component exists, type: {1}, package: {2}, parent: {3}, alias: {4}", $type, $package, $parent, $alias));

    // Check package
    if ($package == '') { 
        $chk = $type . ':' . $alias;
        if (!$package = redis::hget('config:components_package', $chk)) { return false; }
        elseif ($package == 2) { return false; }
    }

    // Ensure component exists
    $chk = implode(":", array($type, $package, $parent, $alias));
    if (redis::sismember('config:components', $chk) == 0) { return false; }

    // Return
    return array($package, $parent, $alias);

}

/**
 * Load a component 
 *
 * Loads a component, and returns the initialized object. 
 *
 * @param string $type The component type (eg. htmlfunc, lib, modal)
 * @param string $alias The alias of the components
 * @param string $package The package alias of the components.
 * @param string $parent The parent alias of the components, if exists
 * @param array $data Optional array with extra data, generally the attributes of the <e:function> tag that is loading the components.
 *
 * @return mixed Returns false if unable to load components, otherwise returns the newly initialized object.
 */
public function load(string $type, string $alias, string $package = '', string $parent = '', array $data = array())
{ 

    // Debug
    debug::add(5, tr("Starting load component, type: {1}, package: {2}, parent: {3}, alias: {4}", $type, $package, $parent, $alias));

    // Check if class exists
    $class_name = $this->get_class_name($type, $alias, $package, $parent);
    if (!app::has($class_name)) { 
        debug::add(4, tr("Component PHP file does not exist, type: {1}, package: {2}, parent: {3}, alias: {4}", $type, $package, $parent, $alias), 'warning');
        return false;
    }

    // Load object via container
    if (!$object = app::make($class_name, ['data' => $data])) { 
        return false;
    }

    // Debug
    debug::add(5, tr("Loaded component, type: {1}, package: {2}, parent: {3}, alias: {4}", $type, $package, $parent, $alias));

    // Return
    return $object;

}

/**
 * Call a function within a component 
 *
 * @param string $function_name The name of the function / method to call.
 * @param string $type The component type (eg. htmlfunc, lib, modal)
 * @param string $alias The alias of the components
 * @param string $package The package alias of the components.
 * @param string $parent The parent alias of the components, if exists
 * @param array $data Optional array of additional name based arguments to pass to the function.
 *
 * @return mixed Returns false if unable to load components, otherwise returns the newly initialized object.
 */
public function call(string $function_name, string $type, string $alias, string $package = '', string $parent = '', array $data = array())
{ 

    // Check if class exists
    $class_name = $this->get_class_name($type, $alias, $package, $parent);
    if (!app::has($class_name)) { 
        debug::add(4, tr("Component PHP file does not exist, type: {1}, package: {2}, parent: {3}, alias: {4}", $type, $package, $parent, $alias), 'warning');
        return false;
    }

    // Call function
    return app::call([$class_name, $function_name], $data);

}

/**
 * Get the full class name of a component 
 *
 * @param string $type The component type (eg. htmlfunc, lib, modal)
 * @param string $alias The alias of the components
 * @param string $package The package alias of the components.
 * @param string $parent Optional parent alias of the component
 *
 * @return string The full class name of the component
 */
private function get_class_name(string $type, string $alias, string $package, string $parent = ''):string
{ 

    // Initialize
    if ($type == 'tabpage') { $type = 'tabcontrol'; }

    // Get class name
    $class_segments = array('', 'apex', $package);
    if ($type != 'lib') { $class_segments[] = $type; }
    if ($parent != '') { $class_segments[] = $parent; }
    $class_segments[] = $alias;

    // Return
    return implode("\\", $class_segments);

}

/**
 * Get the location of a component's PHP file 
 *
 * @param string $type The component type (eg. htmlfunc, modal, lib, etc.)
 * @param string $alias The alias of the components
 * @param string $package The package alias of the components
 * @param string $parent The parent alias of the components, if one exists.
 *
 * @return string The full path to the PHP class file of the components.
 */
public function get_file(string $type, string $alias, string $package, string $parent = ''):string
{ 

    // Debug
    debug::add(5, tr("Getting PHP component file, type: {1}, package: {2}, parent: {3}, alias: {4}", $type, $package, $parent, $alias));

    // Ensure valid components type
    if (!in_array($type, COMPONENT_TYPES)) { 
        return '';
    }

    // Get view file, if needed
    if ($type == 'view') { 
        $php_file = 'views/php/' . $alias . '.php';
        return $php_file;

    // Unit test
    } elseif ($type == 'test') { 
        $php_file = 'tests/' . $package . '/' . $alias . '_test.php';
        return $php_file;
    }

    // Set variables
    $file_type = $type == 'tabpage' ? 'tabcontrol' : $type;

    // Get PHP file
    $php_file =  'src/' . $package . '/';
    if ($file_type != 'lib') { $php_file .= $file_type . '/'; }
    if ($parent != '') { $php_file .= $parent . '/'; }
    $php_file .= $alias . '.php';

    // Debug
    debug::add(5, tr("Got PHP component file, {1}", $php_file));

    // Return
    return $php_file;

}

/**
 * Get the TPL file of any given template. 
 *
 * @param string $type The component type (template, modal, htmlfunc, etc.)
 * @param string $alias The alias of the component
 * @param string $package The package alias of the component
 * @param string $parent The parent alias of the component, if exists
 *
 * @return string The full path to the TPL file of the component
 */
public function get_tpl_file(string $type, string $alias, string $package, string $parent = ''):string
{ 

    // Check view
    if ($type == 'view') { 
        $tpl_file = 'views/tpl/' . $alias . '.tpl';
        return $tpl_file;
    }

    // Get TPL file
if (in_array($type, array('tabpage', 'modal', 'htmlfunc'))) { 
        $tpl_file = 'views/components/' . $type . '/' . $package . '/';
        if ($parent != '') { $tpl_file .= $parent . '/'; }
        $tpl_file .= $alias . '.tpl';

        // Return
        return $tpl_file;
    }

    // Return
    return '';

}

/**
 * Gets all files, .php and .tpl associated with a tab control. 
 *
 * @param string $alias The alias of the tab control.
 * @param string $package The package alias of the tab control.
 *
 * @return array One-dimensional array of files.
 */
public function get_tabcontrol_files(string $alias, string $package):array
{ 

    // Load tab control
    if (!$tab = $this->load('tabcontrol', $alias, $package)) { 
        throw new ComponentException('no_load', 'tabcontrol', '', $alias, $package);
    }

    // Go through tab pages
    $files = array();
    foreach ($tab->tabpages as $tab_alias => $tab_name) { 
        $files[] = 'views/components/tabpage/' . $package . '/' . $alias . '/' . $tab_alias . '.tpl';
        $files[] = 'src/' . $package . '/tabcontrol/' . $alias . '/' . $tab_alias . '.php';
    }

    // Return
    return $files;

}

/**
 * Get all files associated with the given component 
 *
 * @param string $type The component type (template, modal, htmlfunc, etc.)
 * @param string $alias The alias of the component
 * @param string $package The package alias of the component
 * @param string $parent The parent alias of the component, if exists
 *
 * @return array List of all files associated with the component
 */
public function get_all_files(string $type, string $alias, string $package, string $parent = '')
{ 

    // PHP file
    $files = array();
    $php_file = $this->get_file($type, $alias, $package, $parent);
    if ($php_file != '') { $files[] = $php_file; }

    // TPL file
    $tpl_file = $this->get_tpl_file($type, $alias, $package, $parent);
    if ($tpl_file != '') { $files[] = $tpl_file; }

    // Check for tab control
    if ($type == 'tab_control') { 
        $tab_files = $this->get_tabcontrol_files($alias, $package);
        $files = array_merge($files, $tab_files);
    }

    // Return
    return $files;

}


}

