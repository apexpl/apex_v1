<?php
declare(strict_types = 1);

namespace apex\app\io;

use apex\app;
use apex\libc\redis;
use apex\libc\debug;
use apex\app\interfaces\CacheInterface;


/**
 * Cache Handler
 *
 * Service: apex\libc\cache
 *
 * Handles all cache items and related functionality, including set, get, and 
 * expire cache items.
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
 * use apex\libc\cache;
 *
 (
 ( // Set cache item
 * cache::set('some_id', 'my item contents');
 *
 * // Get cache item
 * $data = cache::get('some_id');
 *
 */
class cache implements CacheInterface
{


/**
 * Get cache item.
 *
 * @param string $key The key of the cache item.
 * @param mixed $default_value Optional default value to return, if no cache exists.  Defaults to false.
 *
 * @return mixed Value of the cache item, or default value if not exists.
 */
public function get(string $key, $default_value = false)
{

    // Check if cache enabled
    if (app::_config('core:cache') != 1) { 
        return $default_value;
    }

    // Check if exists
    $key = 'cache:' . $key;
    if (!redis::exists($key)) { 
        $value = $default_value;
    } else { 
        $value = unserialize(redis::get($key));
    }

    // Return
    return $value;

}

/**
 * CHeck if cache item exists
 * 
 * @param string $key Key of chache item to check
 *
 * @return bool Whether or not cache item exists
 */
public function has(string $key):bool
{

    // Check if cache enabled
    if (app::_config('core:cache') != 1) { 
        return false;
    }

    $ok = !redis::exists('cache:' . $key) ? false : true;
    return $ok;

}

/**
 * Set cache item
 *
 * @param string $key Key of the cache item
 * @param mixed $value The value of the item.
 * @param int $ttl The TTL of the cache item.
 */
public function set(string $key, $value, int $ttl = 300)
{

    // Check if cache enabled
    if (app::_config('core:cache') != 1) { 
        return false;
    }

    // Set item
    redis::set('cache:' . $key, serialize($value));
    redis::expire('cache:' . $key, $ttl);

}

/**
 * Delete cache item
 *
 * @param string $key Item to delte
 */
public function delete (string $key)
{

    redis::del('cache:' . $key);

}

/**
 * Clear the cache.
 */
public function clear()
{

    $keys = redis::keys('cache:*');
    foreach ($keys as $key) { 
        redis::del($key);
    }

}

/**
 * Get multiple cache items.
 *
 * @param iterable $keys All keys to retrive from cache.
 * 
 * @return array The values of all found keys.
 */
public function mget(...$keys)
{

    // GO through keys
    $values = array();
    foreach ($keys as $key) { 
        $values[$key] = $this->get($key);
    }

    // Return
    return $values;

}

/**
 * Set multiple cache items.
 *
 * @param array $items The items to key.
 * @param int $ttl The TTL in secions of all items.
 */
public function mset(array $items, int $ttl = 300)
{

    // Go through items
    foreach ($items as $key => $value) { 
        $this->set($key, $value, $ttl);
    }

}

/**
 * Delete multiple cache items.
 *
 * @param iterable $keys The item keys to delete.
 */
public function mdelete(...$keys)
{

    // Delete
    foreach ($keys as $key) { 
        $this->delete($key);
    }

}

}


