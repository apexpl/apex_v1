<?php
declare(strict_types = 1);

namespace apex\app\sys;

use apex\app;
use apex\libc\db;
use apex\libc\debug;
use apex\libc\redis;
use apex\app\exceptions\ComponentException;


/**
 * Components Library
 *
 * Service: apex\libc\components
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
 * use apex\libc\components;
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
    $parts = explode(":", $alias);
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
    $object = app::make($class_name, ['data' => $data]);

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
    if (!isset($data['html'])) { $data['html'] = ''; }

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
    if (isset(COMPONENT_PARENT_TYPES[$type])) { $type = COMPONENT_PARENT_TYPES[$type]; }

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
    if (!in_array($type, array_keys(COMPONENT_TYPES))) { 
        return '';
    }

    // Set replacements
    $replace = array(
        '~package~' => $package, 
        '~parent~' => $parent, 
        '~alias~' => $alias
    );

    // Return
    return strtr(COMPONENT_TYPES[$type], $replace);

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

    // Check type
    if (!in_array($type, array_keys(COMPONENT_TPL_FILES))) { 
        return '';
    }

    // Set replacements
    $replace = array(
        '~package~' => $package, 
        '~parent~' => $parent, 
        '~alias~' => $alias
    );

    // Return
    return strtr(COMPONENT_TPL_FILES[$type], $replace);

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

    // Get tab pages from database
    $pages = db::get_column("SELECT alias FROM internal_components WHERE type = 'tabpage' AND package = %s AND parent = %s", $package, $alias);
    $pages = array_merge($pages, array_keys($tab->tabpages));

    // Go through tab pages
    $files = array();
    foreach ($pages as $tab_alias) { 
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
    if ($type == 'tabcontrol') { 
        $tab_files = $this->get_tabcontrol_files($alias, $package);
        $files = array_merge($files, $tab_files);
    }

    // Return
    return $files;

}

/**
 * Get filename for Github repo.
 *
 * @param string $file The filename retrived from other methodfs within this class (eg. get_all_files()).
 * @param string $pkg_alias The package alias the file belongs to.
 *
 * @return string The relative file path within the Github repo.
 */
public function get_compile_file(string $file, string $pkg_alias):string
{

    // Get the compile file
    $new_file = '';
    if (preg_match("/^src\/(.+?)\/(.+)$/", $file, $match)) { 
        $new_file = $match[1] == $pkg_alias ? 'src/' : 'child/' . $match[1] . '/';
        $new_file .= $match[2];

    } elseif (preg_match("/^tests\/$pkg_alias\/(.+)$/", $file, $match)) { 
        $new_file = 'tests/' . $match[1];

    } elseif (preg_match("/^docs\/$pkg_alias\/(.+)$/", $file, $match)) { 
        $new_file = 'docs/' . $match[1];

    } elseif (preg_match("/^views\/(.+)$/", $file, $match)) { 
        $new_file = $file;
    }

    // Return
    return $new_file;

}






}

