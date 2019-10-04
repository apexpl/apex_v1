<?php
declare(strict_types = 1);

namespace apex\app\interfaces;

/**
 * The template parser interface, which handles all .tpl files.
 */
interface ViewInterface {

/**
 * Parse the template based on URI.
 *
 * @return string The resulting HTML code.
 */
public function parse():string;

/**
 * Parse a chunk of TPL code.
 *
 * @param string $html The TPL / HTML code to parse.
 *
 * @return string The resulting HTML code.
 */
public function parse_html(string $html):string;

/**
 * Assign a merge variable.
 *
 * @param string $key The key of the merge variable.
 * @param mixed $value Value of the merge field, can be string or array.
 */
public function assign(string $key, $value);

/** 
 * Add a callout message to be displayed on next page.
 *
 * @param string $message The callout message.
 * @param string $type Type of callout, 'success', 'info', 'error'.
 */
public function add_callout(string $message, string $type = 'success');

/**
 * Return whether or not template contains callout errors.
 *
 * @return bool If callout errors exist
 */
public function has_errors():bool;

/**
 * Return the page title.
 *
 * @return string The page title.
 */
public function get_title():string;

/**
 * Get an array of all current callouts.
 *
 * @return array The current callouts.
 */
public function get_callouts():array;




}


