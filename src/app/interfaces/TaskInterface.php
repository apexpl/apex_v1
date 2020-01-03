<?php
declare(strict_types = 1);

namespace apex\app\interfaces;

/**
 * Task Interface
 *
 * Interface that handles all child adapater classes within the 
 * core\service\tasks service provider.  These are the methods required to 
 * ensure the task scheduler can manage any type of task.
 */
interface TaskInterface {


/**
 * Execute the task
 *
 * @param string $alias The alias of the task.
 * @param string $data The optional data of the task.
 *
 * @return bool WHether or not the operation completed successfully.
 */
public function process(string $alias, string $data = ''):bool;


/**
 * Get all available tasks
 * the one-time execution of a task within the admin panel.
 * 
 * @return array An associative array of all available tasks, values being the display name in browser.
 */
public function get_available_tasks():array;


/**
 * Get name of a task
 *
 * @param string $alias The alias of the task.
 *
 * @return string The name of the task.
 */
public function get_name(string $alias):string;




}




