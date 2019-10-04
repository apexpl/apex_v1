<?php
declare(strict_types = 1);

namespace apex\app\interfaces;


/**
 * Cache Interface
 */
interface CacheInterface {


/**
 * Get cache item.
 *
 * @param string $key The key of the cache item.
 * @param mixed $default_value Optional default value to return, if no cache exists.  Defaults to false.
 *
 * @return mixed Value of the cache item, or default value if not exists.
 */
public function get(string $key, $default_value = false);


/**
 * CHeck if cache item exists
 * 
 * @param string $key Key of chache item to check
 *
 * @return bool Whether or not cache item exists
 */
public function has(string $key):bool;


/**
 * Set cache item
 *
 * @param string $key Key of the cache item
 * @param mixed $value The value of the item.
 * @param int $ttl The TTL of the cache item.
 */
public function set(string $key, $value, int $ttl);


/**
 * Delete cache item
 *
 * @param string $key Item to delte
 */
public function delete (string $key);


/**
 * Clear the cache.
 */
public function clear();


/**
 * Get multiple cache items.
 *
 * @param iterable $keys All keys to retrive from cache.
 * 
 * @return array The values of all found keys.
 *
public function gets(...$keys);


/**
 * Set multiple cache items.
 *
 * @param array $items The items to key.
 * @param int $ttl The TTL in secions of all items.
 */
public function mset(array $items, int $ttl);


/**
 * Delete multiple cache items.
 *
 * @param iterable $keys The item keys to delete.
 */
public function mdelete(...$keys);


}




