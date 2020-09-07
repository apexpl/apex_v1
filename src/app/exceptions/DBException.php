<?php
declare(strict_types = 1);

namespace apex\app\exceptions;

use apex\app;
use apex\app\exceptions\ApexException;


/**
 * Handles all database related errors including connection, SQL query, and 
 * formatting errors. 
 */
class DBException   extends ApexException
{


    // Set error codes
    private $error_codes = array(
        'no_connect' => "Unable to connect to the mySQL database using the supplied information.  Please ensure the mySQL server is running, and the right connection information is within settings.  If needed, you can update connection information by running 'php apex.php update_master' at the terminal",
        'no_table' => "Unable to perform {action} on table name {table}, as table does not exist within the database.",
        'no_column' => "Unable to perform {action} on column {column} within the table {table}, as column does not exist within the table.",
        'num_rows' => "Unable to determine number of affected rows",
        'insert_id' => "Unable to determine ID# of last insert",
        'begin_transaction' => "Unable to begin transaction",
        'commit' => "Unable to commit transaction",
        'rollback' => "Unable to rollback transaction",
        'query' => "Unable to execute SQL statement: {sql}",
        'invalid_variable' => "Invalid variable passed, is not a {type} as expected: {value}.  SQL Query: {sql}"
    );

    // Placeholder types
    private $placeholder_types = array(
        's' => 'string',
        'i' => 'integer',
        'd' => 'decimal',
        'b' => 'boolean',
        'e' => 'email address',
        'url' => 'URL',
        'ds' => 'date',
        'ts' => 'time',
        'dt' => 'datetime'
    );

/**
 * Construct
 * 
 * @param string $message The exception message.
 * @param string $sql_query The SQL query
 * @param string $server_message The message from the mySQL server.
 * @param string $action The action being performed.
 * @param string $table_name The table name
 * @param string $column_name The column name.
 * @param string $var_type The type of variable, for SQL formatting errors
 * @param string $value The value being formatted. 
 */
public function __construct(string $message, $sql_query = '', $server_message = '', $action = '', $table_name = '', $column_name = '', $var_type = 's', $value = '')
{ 

    // Set vars
    $vars = array(
        'action' => strtoupper($action),
        'table' => $table_name,
        'column' => $column_name,
        'sql' => $sql_query,
        'type' => $this->placeholder_types[$var_type],
        'value' => $value
    );

    // Set variables
    $this->is_generic = 1;
    $this->log_level = 'error';
    $this->code = 500;

    // Get message
    $this->message = $this->error_codes[$message] ?? $message;
    $this->message = tr($this->message, $vars);

    // Set SQL query
    $this->sql_query = $sql_query == '' ? $this->message : $sql_query;

    // Finish message
    $this->message = "DB Error: " . $this->message . '  <br /><br />(' . $server_message . ')';

}


}

