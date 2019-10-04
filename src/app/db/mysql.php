<?php
declare(strict_types = 1);

namespace apex\app\db;

use apex\app;
use apex\app\db\db_connections;
use apex\svc\debug;
use apex\app\interfaces\DBInterface;
use apex\app\exceptions\DBException;


/**
 * Handles all database communication between the software and the mySQL 
 * database.  Please refer to the developer documentation for more details on 
 * methods within this class. 
 */
class mysql   extends db_connections implements DBInterface
{




    // Injected properties
    private $debug;

    // Properties
    private $tables = [];
    private $columns = [];
    private $prepared = [];
    private $raw_sql;
    private $conn;


/**
 * Initiates a connection to the database, and returns the resulting 
 * connection. 
 *
 * @param string $dbname The database name
 * @param string $dbuser The database username
 * @param string $dbpass Optional database password
 * @param string $dbhost optional database host, defaults to 'localhost'
 * @param int $dbport Optional database port, defaults to 3306
 *
 * @return object The resulting database connection
 */
public function connect(string $dbname, string $dbuser, string $dbpass = '', string $dbhost = 'localhost', int $dbport = 3306)
{ 

    // Connect
    if (!$conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname, $dbport)) { 
        throw new DBException('no_connect');
    }

    // Set timezone to UTC
    mysqli_query($conn, "SET TIME_ZONE = '+0:00'");

    // Debug
    debug::add(4, tr("Connected to database, name: {1} user: {2}", $dbname, $dbuser));

    // Return
    return $conn;

}

/**
 * Executes the 'SHOW TABLES' command, and returns a one-dimensional array of 
 * all tables within the database. 
 *
 * @retirm array One-dimensional array of all table names within the database. 
 *  
 */
public function show_tables():array
{ 

    // Check if tables already retrieved
if (count($this->tables) > 0) { 
        return $this->tables;
    }

    // Get tables
    $result = $this->query("SHOW TABLES");
    while ($row = $this->fetch_array($result)) { 
        $this->tables[] = $row[0];
    }

    // Return
    return $this->tables;

}

/**
 * Returns an array of all columns within the given table name. 
 *
 * @param string $table_name The name of the table to retrieve column names from.
 * @param bool $include_types Whether or not to include the types of columns as the value of array
 *
 * @return array One-dimensional array of column names.
 */
public function show_columns(string $table_name, bool $include_types = false):array
{ 

    // cHECK IF COLUMNS ALREADY GOTTEN
    if (isset($this->columns[$table_name]) && is_array($this->columns[$table_name]) && count($this->columns[$table_name]) > 0) { 
        if ($include_types == true) { 
            return $this->columns[$table_name];
        } else { 
            return array_keys($this->columns[$table_name]);
        }
    }

    // Get column names
    $this->columns[$table_name] = array();
    $result = $this->query("DESCRIBE $table_name");
    while ($row = $this->fetch_array($result)) { 
        $this->columns[$table_name][$row[0]] = $row[1];
    }

    // Return
    if ($include_types === true) { 
        return $this->columns[$table_name];
    } else { 
        return array_keys($this->columns[$table_name]);
    }


}

/**
 * Inserts a new record into the database. 
 *
 * @param mixed $args First element is the table name, second an associative array of values to insert.
 */
public function insert(...$args)
{ 

    // Check if table exists
    $table_name = array_shift($args);
    if (!$this->check_table($table_name)) { 
        throw new DBException('no_table', '', '', 'insert', $table_name);
    }

    // Set variables
    $values = array();
    $placeholders = array();
    $columns = $this->show_columns($table_name, true);

    // Generate SQL
    $sql = "INSERT INTO $table_name (" . implode(', ', array_keys($args[0])) . ") VALUES (";
    foreach ($args[0] as $column => $value) { 

        // Check if column exists
        if (!isset($columns[$column])) { 
            throw new DBException('no_column', '', '', 'insert', $table_name, $column);
        }

        // Add variables to sql
        $placeholders[] = $this->get_placeholder($columns[$column]);
        $values[] = $value;
    }
    $sql .= implode(", ", $placeholders) . ')';

    // Execute SQL
    $this->query($sql, ...$values);

}

/**
 * Updates the database via the 'UPDATE' command. 
 *
 * @param iterable $args First element is table name, second is associative array of update volumns / values, third is WHERE SQL, and others are placeholders values for where sql.
 */
public function update(...$args)
{ 

    // Set variables
    $table_name = array_shift($args);
    $updates = array_shift($args);

    // Check if table exists
    if (!$this->check_table($table_name)) { 
        throw new DBException('no_table', '', '', 'update', $table_name);
    }

    // Set variables
    $values = array();
    $placeholders = array();
    $columns = $this->show_columns($table_name, true);

    // Generate SQL
    $sql = "UPDATE $table_name SET ";
    foreach ($updates as $column => $value) { 

        // Ensure column exists in table
        if (!isset($columns[$column])) { 
            throw new DBException('no_column', '', '', 'update', $table_name, $column);
        }

        // Set SQL variables
        $placeholders[] = "$column = " . $this->get_placeholder($columns[$column]);
        $values[] = $value;
    }

    // Finish SQL
    $sql .= implode(", ", $placeholders);
    if (isset($args[0]) && isset($args[1])) { 
        $sql .= " WHERE " . array_shift($args);
    }

    // Execute  SQL
    $this->query($sql, ...$values, ...$args);

}

/**
 * Delete one or more rows from a table via the DELETE sataement. 
 *
 * @param mixed $args First element is the table name, second is the WHERE clause, and third and others are values of placeholders.
 */
public function delete(...$args)
{ 

    // Check if table exists
    $table_name = array_shift($args);
    if (!$this->check_table($table_name)) { 
        throw new DBException('no_table', '', '', 'delete', $table_name);
    }

    // Format SQL
    $sql = "DELETE FROM $table_name";
    if (isset($args[0]) && $args[0] != '') { 
        $sql .= ' WHERE ' . array_shift($args);
    }

    // Execute SQL
    $this->query($sql, ...$args);

}

/**
 * Gets a single row from the database, and if the SQL statement matches more 
 * than one row, only returns the first row. 
 *
 * @param mixed $args First element is the SQL statement, other elements are values of the place holders.
 *
 * @return array Array of key-value pairs of the one row retrieved.  False if no row found.
 */
public function get_row(...$args)
{ 

    // Get first row
    $result = $this->query(...$args);
    if (!$row = $this->fetch_assoc($result)) { return false; }

    // Return
    return $row;

}

/**
 * A short-hand version of the above 'get_row()' method, and used if you're 
 * retrieving a specific row by the 'id' column. 
 *
 * @param string $table_name The table name to retrive the row from.
 * @param string $id_number The value of the 'id' column to retrieve.
 *
 * @return array Array containing key-value pairs of the row retrieved.
 */
public function get_idrow($table_name, $id_number)
{ 

    //Check table
    if (!$this->check_table($table_name)) { 
        throw new DBException('no_table', '', '', 'select', $table_name);
    }

    // Get first row
    if (!$row = $this->get_row("SELECT * FROM $table_name WHERE id = %s ORDER BY id LIMIT 0,1", $id_number)) { 
        return false;
    }

    // Return
    return $row;

}

/**
 * Retrieves a single column from a table, and returns a one-dimensional array 
 * of the values. 
 *
 * @param mixed $args First element is the SQL statement, other elements are values of the place holders.
 *
 * @return array One-dimensional array containing the values of the column.
 */
public function get_column(...$args)
{ 

    // Get column
    $cvalues = array();
    $result = $this->query(...$args);
    while ($row = $this->fetch_array($result)) { 
        $cvalues[] = $row[0];
    }

// Return
    return $cvalues;

}

/**
 * Get two column hash 
 *
 * Retrieves two columns from a database (ie. 'id', and some other column), 
 * and returns an array of key-value pairs of the results. 
 * @param mixed $args First element is the SQL statement, other elements are values of the place holders.
 *
 * @return array Array of key-value pairs of the results.
 */
public function get_hash(...$args)
{ 

    // Get hash
    $vars = array();
    $result = $this->query(...$args);
    while ($row = $this->fetch_array($result)) { 
        $vars[$row[0]] = $row[1];
    }

// Return
    return $vars;

}

/**
 * Gets a single column from a single row, and returns the resulting scalar 
 * variable. 
 *
 * @param mixed $args First element is the SQL statement, other elements are values of the place holders.
 *
 * @return string The value of the column from the first row.  Returns false if no rows matched.
 */
public function get_field(...$args)
{ 

    // Execute SQL query
    $result = $this->query(...$args);

    // Return result
    if (!$row = $this->fetch_array($result)) { return false; }
    return $row[0];

}

/**
 * Executes any SQL statement against the database, and returns the result. 
 *
 * @param mixed $args First element is the SQL statement, other elements are values of the place holders.
 *
 * @return mixed The result of the query.
 */
public function query(...$args)
{ 

    //Format SQL
    list($hash, $bind_params, $values) = $this->format_sql($args);

    // Debug
    debug::add(3, tr("Executed SQL: {1}", $this->raw_sql));
    debug::add_sql($this->raw_sql);

    // Bind params
    if (count($values) > 0) { 
        mysqli_stmt_bind_param($this->prepared[$hash], $bind_params, ...$values);
    }

    // Execute SQL
    if (!mysqli_stmt_execute($this->prepared[$hash])) { 
        throw new DBException('query', $this->raw_sql, mysqli_error($this->conn));
    }

    // Get result
    $result = mysqli_stmt_get_result($this->prepared[$hash]);

    // Return
    return $result;

}

/**
 * The standard mysqli_fetch_array() function, except with error checking. 
 *
 * @param mixed $result the mysqli_result() object.
 */
public function fetch_array($result)
{ 

    // Get row
    if (!$row = mysqli_fetch_array($result)) { 
        return false;
    }

    // Return
    return $row;

}

/**
 * The standard mysqli_fetch_assoc() function except with error checking. 
 *
 * @param mixed $result The mysqli_result object.
 */
public function fetch_assoc($result)
{ 

    // Get row
    if (!$row = mysqli_fetch_assoc($result)) { 
        return false;
    }

    // Return
    return $row;

}

/**
 * Returns the number of rows affected by the previous SQL statement. 
 *
 * @param mixed $result The mysqli result.
 */
public function num_rows($result)
{ 

    // Get num rows
    if (!$num = mysqli_num_rows($result)) { 
        throw new DBException('num_rows');
    }
    if ($num == '') { $num = 0; }

    // Return
    return $num;

}

/**
 * Returns the ID# of the previous INSERT statement. 
 */
public function insert_id()
{ 

    // Get insert ID
    return mysqli_insert_id($this->conn);

}

/**
 * Format SQL statement 
 *
 * Formats the SQL by sanitizing the values passed as additional parameters, 
 * and replacing the placeholders within the SQL statement with them. 
 *
 * @param mixed $args First element is the SQL statement, other elements are values of the place holders.
 */
private function format_sql($args)
{ 

    // Get connection
    $type = preg_match("/^(select|show|describe) /i", $args[0]) ? 'read' : 'write';
    $conn = $this->get_connection($type);
    $this->conn = $conn;


    // Set variables
    $x=1;
    $values = array();
    $bind_params = '';
    $raw_sql = $args[0];

    // Go through args
    preg_match_all("/\%(\w+)/", $args[0], $args_match, PREG_SET_ORDER);
    foreach ($args_match as $match) { 
        $value = $args[$x] ?? '';

        // Check data type
        $is_valid = true;
        if ($match[1] == 'i' && $value != '0' && !filter_var($value, FILTER_VALIDATE_INT)) { $is_valid = false; }
        elseif ($match[1] == 'd' && $value != '' && !preg_match("/^[0-9]+(\.[0-9]{1,})?$/", (string) abs($value))) { $is_valid = false; }
        elseif ($match[1] == 'b' && $value != '0' && !filter_var($value, FILTER_VALIDATE_INT)) { $is_valid = false; }
        elseif ($match[1] == 'e' && !filter_var($value, FILTER_VALIDATE_EMAIL)) { $is_valid = false; }
        elseif ($match[1] == 'url' && !filter_var($value, FILTER_VALIDATE_URL)) { $is_valid = false; }
        elseif ($match[1] == 'ds') { 
            if (preg_match("/^(\d\d\d\d)-(\d\d)-(\d\d)$/", $value, $dmatch)) { 
                if (!check_date($dmatch[2], $dmatch[3], $dmatch[1])) { $is_valid = false; }
            } else { $is_valid = false; }
        } elseif ($match[1] == 'ts' && !preg_match("/^\d\d:\d\d:\d\d$/", $value)) { $is_valid = false; }
        elseif ($match[1] == 'dt') { 
            if (preg_match("/^(\d\d\d\d)-(\d\d)-(\d\d) \d\d:\d\d:\d\d$/", $value, $dmatch)) { 
                if (!check_date($dmatch[2], $dmatch[3], $dmatch[1])) { $is_valid = false; }
            } else { $is_valid = false; }
        }
        

        // Process invalid argument, if needed
        if ($is_valid === false) { 
            throw new DBException('invalid_variable', $args[0], '', '', '', '', $match[1], $value);
        }

        // Add bind_param
        if ($match[1] == 'i' || $match[1] == 'b') { $bind_params .= 'i'; }
        elseif ($match[1] == 'd') { $bind_params .= 'd'; }
        elseif ($match[1] == 'blobl') { $bind_params .= 'b'; }
        else { $bind_params .= 's'; }

        // Format value
        if ($match[1] == 'ls') { $value = '%' . $value . '%'; }
        $values[] = $value;

        // Replace placeholder in SQL
        $args[0] = preg_replace("/$match[0]/", '?', $args[0], 1);
        $raw_sql = preg_replace("/$match[0]/", "'" . mysqli_real_escape_string($conn, (string) $value) . "'", $raw_sql, 1);

    $x++; }

    // Check for prepared statement
    $hash = 's' . crc32($args[0]);
    if (!isset($this->prepared[$hash])) { 
        if (!$this->prepared[$hash] = mysqli_prepare($conn, $args[0])) { 
            throw new DBException('query', $raw_sql, mysqli_error($conn));
        }
    }
    $this->raw_sql = $raw_sql;

    // Return
    return array($hash, $bind_params, $values);

}

/**
 * Checks to see whether or not a table exists within the database. 
 *
 * @param string $table_name The table name to check.
 *
 * @return bool WHether or not the table exists in the database.
 */
public function check_table($table_name):bool
{ 

    // Get table names
    $tables = $this->show_tables();
    $ok = in_array($table_name, $tables) ? true : false;

    // Return
    return $ok;

}

/**
 * Begin transaction 
 *
 * Begins a new transaction within the database, meaning no further SQL 
 * queries will be executed against the database until a COMMIT is executed. 
 */
public function begin_transaction()
{ 

    // Get connection
    $conn = $this->get_connection('write');

    // Begin transaction
    if (!mysqli_begin_transaction($conn)) { 
        throw new DBException('begin_transaction');
    }

    //EwReturn
    return true;

}

/**
 * Commit transaction 
 *
 * Commits a transaction, meaning any SQL queries that were executed after the 
 * transaction will now be written to the database. 
 */
public function commit()
{ 

    // Get connection
    $conn = $this->get_connection('write');

    // Commit transaction
    if (!mysqli_commit($conn)) { 
        throw new DBException('commit');
    }

    //EwReturn
    return true;

}

/**
 * Rollback transaction 
 *
 * Performs a rollback on the previously started transaction, meaning none of 
 * the SQL statements executed since the transaction began will be applied to 
 * the database. 
 */
public function rollback()
{ 

    // Get connection
    $conn = $this->get_connection('write');

    // Rollback transaction
    if (!mysqli_rollback($conn)) { 
        throw new DBException('rollback');
    }

    //EwReturnr
    return true;
 
}

/**
 * Get placeholder based on column type 
 *
 * @param string $col_type The type of column
 */
private function get_placeholder(string $col_type)
{ 

    // Get placeholder
    if (strtolower($col_type) == 'tinyint(1)') { $type = '%b'; }
    elseif (preg_match("/int\(/i", $col_type)) { $type = '%i'; }
    elseif (preg_match("/decimal/i", $col_type)) { $type = '%d'; }
    elseif (strtolower($col_type) == 'blob') { $type = '%blob'; }
    else { $type = '%s'; }

    // Return
    return $type;

}


}

