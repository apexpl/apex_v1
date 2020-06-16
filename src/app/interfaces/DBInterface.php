<?php

namespace apex\app\interfaces;

/**
 * Database interface
 */
interface DBInterface {


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
public function connect(string $dbname, string $dbuser, string $dbpass, string $dbhost, int $dbport);


/**
 * Executes the 'SHOW TABLES' command, and returns a one-dimensional array of 
 * all tables within the database. 
 *
 * @retirm array One-dimensional array of all table names within the database. 
 *  
 */
public function show_tables():array;


/**
 * Returns an array of all columns within the given table name. 
 *
 * @param string $table_name The name of the table to retrieve column names from.
 * @param bool $include_types Whether or not to include the types of columns as the value of array
 *
 * @return array One-dimensional array of column names.
 */
public function show_columns(string $table_name, bool $include_types = false):array;


/**
 * Clear cache
 *
 * Clear the $tables and $columns properties within the class, used to 
 * any changes to the schema show up during testing / checks during package installation / upgrades.
 */
public function clear_cache();


/**
 * Inserts a new record into the database. 
 *
 * @param mixed $args First element is the table name, second an associative array of values to insert.
 */
public function insert(...$args);


/**
 * Insert to update on duplicate key statement
 *
 * @param mixed $args First element is the table name, second an associative array of values to insert.
 */
public function insert_or_update(...$args);


/**
 * Updates the database via the 'UPDATE' command. 
 *
 * @param iterable $args First element is table name, second is associative array of update volumns / values, third is WHERE SQL, and others are placeholders values for where sql.
 */
public function update(...$args);


/**
 * Delete one or more rows from a table via the DELETE sataement. 
 *
 * @param mixed $args First element is the table name, second is the WHERE clause, and third and others are values of placeholders.
 */
public function delete(...$args);


/**
 * Gets a single row from the database, and if the SQL statement matches more 
 * than one row, only returns the first row. 
 *
 * @param mixed $args First element is the SQL statement, other elements are values of the place holders.
 *
 * @return array Array of key-value pairs of the one row retrieved.  False if no row found.
 */
public function get_row(...$args);


/**
 * A short-hand version of the above 'get_row()' method, and used if you're 
 * retrieving a specific row by the 'id' column. 
 *
 * @param string $table_name The table name to retrive the row from.
 * @param string $id_number The value of the 'id' column to retrieve.
 *
 * @return array Array containing key-value pairs of the row retrieved.
 */
public function get_idrow($table_name, $id_number);

/**
 * Retrieves a single column from a table, and returns a one-dimensional array 
 * of the values. 
 *
 * @param mixed $args First element is the SQL statement, other elements are values of the place holders.
 *
 * @return array One-dimensional array containing the values of the column.
 */
public function get_column(...$args);


/**
 * Get two column hash 
 *
 * Retrieves two columns from a database (ie. 'id', and some other column), 
 * and returns an array of key-value pairs of the results. 
 * @param mixed $args First element is the SQL statement, other elements are values of the place holders.
 *
 * @return array Array of key-value pairs of the results.
 */
public function get_hash(...$args);


/**
 * Gets a single column from a single row, and returns the resulting scalar 
 * variable. 
 *
 * @param mixed $args First element is the SQL statement, other elements are values of the place holders.
 *
 * @return string The value of the column from the first row.  Returns false if no rows matched.
 */
public function get_field(...$args);


/**
 * Executes any SQL statement against the database, and returns the result. 
 *
 * @param mixed $args First element is the SQL statement, other elements are values of the place holders.
 *
 * @return mixed The result of the query.
 */
public function query(...$args);


/**
 * Returns the ID# of the previous INSERT statement. 
 */
public function insert_id();


/**
 * Checks to see whether or not a table exists within the database. 
 *
 * @param string $table_name The table name to check.
 *
 * @return bool WHether or not the table exists in the database.
 */
public function check_table($table_name):bool;


/**
 * Begin transaction 
 *
 * Begins a new transaction within the database, meaning no further SQL 
 * queries will be executed against the database until a COMMIT is executed. 
 */
public function begin_transaction();


/**
 * Commit transaction 
 *
 * Commits a transaction, meaning any SQL queries that were executed after the 
 * transaction will now be written to the database. 
 */
public function commit();


/**
 * Rollback transaction 
 *
 * Performs a rollback on the previously started transaction, meaning none of 
 * the SQL statements executed since the transaction began will be applied to 
 * the database. 
 */
public function rollback();


/**
 * Add time
 *
 * @param string $period The period to add (ie. hours, days, weeks, etc.).
 * @param int $length The length of the period to add.
 * @param string $from_date The starting date, formatted in YYYY-MM-DD HH:II:SS
 * @param bool $return_datestamp Whether or not to return the full datetime stamp, or the number is seconds from UNIX epoch.
 * 
 * @return string The resulting date after addition.
 */
public function add_time(string $period, int $length, string $from_date, bool $return_datestamp = true);


/**
 * Subtract time
 *
 * @param string $period The period to subtract (ie. hours, days, weeks, etc.).
 * @param int $length The length of the period to subtract.
 * @param string $from_date The starting date, formatted in YYYY-MM-DD HH:II:SS
 * @param bool $return_datestamp Whether or not to return the full datetime stamp, or the number is seconds from UNIX epoch.
 * 
 * @return string The resulting date after subtraction.
 */
public function subtract_time(string $period, int $length, string $from_date, bool $return_datestamp = true);


}


