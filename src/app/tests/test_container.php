<?php
declare(strict_types = 1);

namespace apex\app\tests;

use apex\app;
use apex\core\admin;


/**
 * Helps to allow for testing of the http 
 * dependncy injection container at /src/app/sys/container.php
 */
class test_container
{

/**
 * String test
 *
 * @param string $name The name
 */
public function string_test(string $name) { return true; }

/**
 * Get money / float
 *
 * @param float $amount The amount
 */
public function float_test(float $amount) { return true; }

/**
 * Integer test
 *
 * @param int $num The number
 */
public function integer_test(int $num) { return true; }

/**
 * Boolean test
 *
 * @param bool $ok The boolean
 */
public function boolean_test(bool $ok) { return true; }

/**
 * object test
 */
public function object_test(object $user) { return $user; }

/**
 * Test instanceOf
 */
public function instanceof_test(admin $admin) { return $admin; }

/**
 * test null
 */
public function null_test($value = null) { return $value; }

}




