<?php
declare(strict_types = 1);

namespace apex\app\exceptions;

use apex\app;
use apex\app\exceptions\ApexException;


/**
 * Handles all component related errors, such as no component exists, class 
 * name does not exist, unable to load PHP class, etc. 
 */
class ComponentException   extends ApexException
{

    // Properties
    private $error_codes = array(
        'not_exists_alias' => "The component of type {type} does not exist with the alias: {comp_alias}",
        'not_exists' => "The component of type: '{type}' does not exist with package: '{package}', parent: '{parent}', alias: '{alias}'",
        'hash_no_redis' => "The hash does not exist within redis, {comp_alias}.  You may want to resync the redis database.",
        'undefined_type' => "You did not specify a component type to create.  Proper usage:\n\n\tphp apex.php create TYPE PACKAGE:[PARENT:]ALIAS [OWNER]\n",
        'invalid_type' => "Component type is invalid, and is not supported, {type}",
        'invalid_comp_alias' => "Invalid component alias specified for type '{type}', alias: {comp_alias}",
        'no_parent' => "Unable to create component of type {type} with alias {comp_alias} as no parent was specified, and a parent is required for this component",
        'parent_not_exists' => "Unable to add component of type {type} with comp alias {comp_alias} as the parent does not exist within the system",
        'no_owner' => "Unable to add component of type {type} as no owner package was specified, and is required for this component",
        'no_worker_routing_key' => "Unable to add new 'worder' component as no routing key was defined, and is required for this component",
        'no_load' => "Unable to load component of type: {type}, package: {package}, parent: {parent}, alias: {alias}",
        'no_php_file' => "Unable to determine the location of the PHP file for component, type: {type}, package: {package}, parent: {parent}, alias: {alias}",
        'php_file_exists' => "PHP file already exists for the component, type: {type}, package: {package}, parent: {parent}, alias: {alias}",
        'invalid_template_uri' => "Invalid template URI specified, {alias}"
    );

/**
 * Construct 
 * 
 * @param string $message The exception message
 * @param string $type The type of component.
 * @param string $comp_alias The full component alias.
 * @param string $alias The component alias
 * @param string $package The package alias.
 * @param string $parent The parent alias.
 */
public function __construct($message, $type = '', $comp_alias = '', $alias = '', $package = '', $parent = '')
{ 
    // Start variables
    $vars = array(
        'type' => $type,
        'comp_alias' => $comp_alias,
        'alias' => $alias,
        'package' => $package,
        'parent' => $parent
    );

    // Check comp_alias
    if (preg_match("/^(\w+):(\w+):(\w+)$/", $comp_alias, $match)) { 
        $vars['package'] = $match[1];
        $vars['parent'] = $match[2];
        $vars['alias'] = $match[3];
    } elseif (preg_match("/^(\w+):(\w+)$/", $comp_alias, $match)) { 
        $vars['package'] = $match[1];
        $vars['parent'] = '';
        $vars['alias'] = $match[2];
    }

    // Set variables
    $this->log_level = 'error';
    $this->is_generic = 1;


    // Get message
    $this->message = $this->error_codes[$message] ?? $message;
    $this->message = tr($this->message,$vars);

}


}

