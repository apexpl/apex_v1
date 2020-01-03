
# db:: Service -- Back-End Database (mySQL)

#### Class: [apex\app\db\mysql](https://apex-platform.org/api/classes/apex.app.db.mysql.html)

This service provides access to the back-end database, which by default is mySQL, although other database
engines such as PostgreSQL or Oracle can be easily integrated.  As with all services, the methods can be
accessed statically providing easy and efficient access.

**Example**

~~~php
namespace apex;

use apex\app;
use apex\libc\db;

$value = 'john';
$rows = db::query("SELECT * FROM table_name WHERE some_column = %s", $value);
foreach ($rows as $row) {
    // Do something
}
~~~


### SQL Placeholders

Placeholders are fully supported to properly sanitize all SQL queries, helping prevent SQL injection attacks.
All placeholders begin with the **%** sign, followed by one or two characters.  For example:

~~~php
$status = 'inactive';
$group_id = 2;

$rows = db::query("SELECT * FROM users WHERE status = %s AND group_id = %i", $status, $group_id);
foreach ($rows as $row) {
    // Do something
}
~~~

In the above example, the value of the status (%s) column must be a string, and the value of the group_id (%i)
column must be an integer.  The actual values are then passed as additional parameters to the function, and
are properly checked and sanitized before being sent to the mySQL database engine.  The below table lists all
available placeholders:

Placeholder | Description 
------------- |------------- 
%s | String 
%i | Integer, no decimal points 
%d | Decimal 
%b | Boolean, only allowed values are 1 / 0 
%e | E-mail address 
%url | URL 
%ds | Date stamp, must be
formatted in YYYY-MM-DD 
%ts | Timestampe, must be formatted in HH:II:SS 
%ls | For the LIKE operand.  Sanitizes the value, and surrounds it with '%' characters.  For example, the value "john" becomes "'%john%'"


### db::query(string $sql, array $args)

**Description:** Performs any SQL statement against the database, but is generally used for SELECT statements,
and simply returns the result of the `mysqli_query()` function.

**Example**

~~~php
$rows = db::query("SELECT id,username,full_name FROM users WHERE group_id = %i AND status = %s", $group_id, $status);
foreach ($rows as $row) {
    // Do something
}
~~~


### db::insert(string $table_name, array $values)

**Description:** Inserts a new row into the specified database table.

**Parameters**

Variable | Type | Description 
------------- |------------- |------------- 
`$table_name` | string | The table name to insert a record into. 
`$values` | array | Array of key-value pairs that denote the column names and the values to insert.

** Example**

~~~php
db::insert('blog_posts', array(
    'title' => app::_post('blog_title'),
    'contents' => app::_post('blog_contents'))
);
~~~


### db::update(string $table_name, array $values, string $where_sql, array $args)

**Description:** Updates one or more rows within the provided table name of the database.

**Parameters**

Variable | Type | Description 
------------- |------------- |------------- 
`$table_name` | string | The table name to update rows within. 
`$values` | array | Key-value pairs of the column names and values to update them to. 
`$where_sql~ | string | Optional, and the WHERE clause within the SQL statement (eg. "id = %i") 
`$args` | array | A one dimensional array of values to fill the placeholders within the `$hwere_sql` clause.

** Example**

~~~php
db::update('blog_posts', array(
    'title' => app::_post('blog_title'),
    'contents' => app::_post('blog_contents')),
"id = %i", app::_post('blog_id'));
~~~


### db::delete(string $table_name, string $where_sql, array $args)

**Description:** Deletes rows from the specified table.

**Parameters**

Variable | Type | Description 
------------- |------------- |------------- 
`$table_name` | string | The table name to delete rows from. 
`$where_sql` | string | There WHERE clause within the SQL statement (eg. "type = %s") 
`$args` | array | One-dimensional array of values to replace the placeholders within the `$where_sql` clause.

**Example**

~~~php
db::delete('blog_posts', 'type = %s AND blog_id = %i', $type, $blog_id);
~~~


### array db::get_row(string $sql, array $args)</api

**Description:** Get the first row found using the given SQL query, and returns an associative array of the
values.  Returns false if no row exists.

**Example**

~~~php
if (!$row = db::get_row("SELECT * FROM some_table WHERE title = %s AND status = %s", $title, $status)) {
    echo "No row exists with these variables.";
}

print_r($row);
~~~


###`array db::get_idrow(string $table_name in $id_number)

**Description:** Similiar to the `get_row()` function, and only returns one row from the database, but just a
quicker way to look up rows based strictly on the "id" column of the database table if you have it.

Variable | Type | Description 
------------- |------------- |------------- 
`$table_name` | string | The table name to retrive the row from. 
`$id_number` | int | The ID# of the record to retrive, must match the "id" column of the database table.  Returns false if not row exists.

**Example**

~~~php
if (!$row = db::get_idrow('users', $userid)) {
    echo "No row exists with the ID# $userid\n";
}

print_r($row);
~~~


### array db::get_column(string $sql, array $args)

**Description:** Returns a one-dimensional array of one specific column within a database table.

**Example**

~~~php
$types = db::get_column("SELECT type FROM some_type WHERE status = %s", $status);
print_r($types);
~~~


### array db::get_hash(string $sql, array $args)

**Description:* Returns an associative array of the two columns defined within the SQL statement.  Useful for
creating a quick key-value pair from a database table.

**Example**

~~~php
$groups = db::get_hash("SELECT id,name FROM users_groups");
foreach ($groups as $group_id => $name) {
    ... do something ...
}
~~~


### string db::get_field(string $sql, array $args)

**Description:** Returns the first column from the first row of the resulting SQL statement.  Useful for
getting a single field from a single row from the database.

**Example**

~~~php
if (!$name = db::get_field("SELECT full_name FROM users WHERE id = %i", $userid)) {
    echo "Unable to find name for user ID# $userid";
}

echo "Name is: $name\n":
~~~


### int db::insert_id()

**Description:** Simply returns the ID# of the last row inserted into a table with an id column that auto
increments.


### array db::show_tables()

**Description:** Returns a one-dimensional array of all tables within the database.


### array db::show_columns(string $table_name, bool $include_types = false)

** Description:** Returns an one-dimenational array of all columns within the given table provided.  If
`$include_types` is true, will return an associative array that includes the column type.


### db::begin_transaction()

**Description:** Begins a new database transaction.


### db::commit()

**Description:** Submits the currently open database transaction.


### db::rollback()

**Description:** Rollsback the currently open database transaction.


