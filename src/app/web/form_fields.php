<?php
declare(strict_types = 1);

namespace apex\app\web;

use apex\app;
use apex\libc\debug;


/**
 * Class that handles form field manipulation within 
 * worker / consumer classes.
 */
class form_fields
{

    // Properties
    private array $add = [];
    private array $remove = [];
    private string $current_position = 'bottom';


/**
 * Add a field
 *
 * @param string $name The name of the form field.
 * @param array $vars The variables of the form field (see docs).
 * @param string $position, Optional position of the form field, otherwise will go under current position.  Defaults to 'bottom'.
 */
public function add(string $name, array $vars, string $position = '')
{

    // Set position, if needed
    if ($position == '') { $position = $this->current_position; }

    // Add to array
    $this->add[] = [
        'position' => $position, 
        'name' => $name, 
        'vars' => $vars
    ];

}

/**
 * Remove a form field.
 *
 * @param string $name The name of the form field to remove.
 */
public function remove(string $name)
{

    // Add to removal
    $this->remove[] = $name;

}

/**
 * Get current position.
 *
 * Sets the current position / pointer within the HTML form.  All 
 * future form fields added will be added after this field.
 *
 * @param string $name The name of the form field to set position to.
 */
public function set_position(string $name)
{
    $this->current_position = $name;
}

/**
 * Gather the results to be send back to caller function.
 *
 * @return array The results of all form field modifications.
 */
public function getall():array
{

    // Return
    return [
        'add' => $this->add, 
        'remove' => $this->remove
    ];

}

}



