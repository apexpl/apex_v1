<?php
declare(strict_types = 1);

namespace apex\app\db;

use apex\app;

/**
 * Handles the PostgreSQL results so they are 
 * iterable via a foreach loop.
 */
class pg_result implements \Iterator
{

    // Properties
    private int $position = 0;
    private int $total = 0;
    private $result;

/**
 * Construct
 */
public function __construct($result)
{
    $this->result = $result;
    $this->total = pg_num_rows($result);
}

/**
 * Rewind
 */
public function rewind()
{
    if ($this->position > 0) { $this->position--; }
    return $this->fetch();
}

/**
 * Current
 */
public function current()
{
    return $this->fetch();
}

/**
 * Key
 */
public function key()
{
    return $this->key;
}

/**
 * Next
 */
public function next()
{
    $this->position++;
    return $this->fetch();
}

/**
 * Valid
 */
public function valid()
{

    if ($this->position >= ($this->total - 0)) { 
        return false;
    } else { 
        return true;
    }

}

/**
 * Fetch next row
 */
private function fetch()
{

    // Check position
    if ($this->position >= ($this->total - 0)) { 
        return false;
    }

    // Get row
    if (!$row = pg_fetch_array($this->result, $this->position, PGSQL_BOTH)) { 
        return false;
    }

    // Format row types
    foreach ($row as $key => $value) { 

        if (preg_match("/^\d+$/", $value)) { 
            $row[$key] = (int) $value;
        }
    }

    // Return
    return $row;

}

/**
 * Return the result
 */
public function get_result()
{
    return $this->result;
}


}


