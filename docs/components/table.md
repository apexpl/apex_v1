
# Data Table Component

&nbsp; | &nbsp;
------------- |------------- 
**Description:** | Data tables are quality, stylish tables with full AJAX functionality including pagination, sort, search and row deletion, that allow you to easily display any data from the database.  Flexible, customizable, and can be easily developed in a few short minutes.
**Create Command:** | `php apex.php create table PACKAGE:ALIAS`
**File Location:** | /src/PACKAGE?table/ALIAS.php
**Namespace:** | apex\PACKAGE\table
**HTML Tag:** | `<e:function alias="display_table" table="PACKAGE:ALIAS">`


## Properties

The below table describes all properties contained within this class.

Variable | Required | Type | Notes
------------- |------------- |------------- |-------------
`$columns` | Yes | array | An array of key-value pairs that define the columns to display within the table.  The keys should generally try to be the same as the column names of the database table although not required, and the values are what is displayed within the browser as the header columns.
`$sortable` | No | array | One dimensional array that contains any columns that are sortable.  These are the keys from the $this->columns array, and should only be exact column names from the database table.  For any columns defined here, up/down arrows will appear around the name of the column in the header, allowing the user to sort the table via AJAX, meaning they do not have to wait for their browser to reload.
`$rows_per_page` | Yes | int | The number of rows per-page to display.  If the table has more rows, pagaination links will appear in the bottom-left corner of the table powered by AJAX, meaning the user does not have to wait for the browser to reload to scroll through pages.
`$has_search` | No | bool | A boolean that defines whether this table support quick search via AJAX.  If yes, a small search textbox will appear in the top-right corner above the table.  Upon entering a search term, the dataset will be queried via AJAX, and the table rows will be replaced with any matching rows.  Defaults to false.
`$form_field` | No | string | The type of form field to display beside each row.  If "radio" opr "checkbox" a small column will appear at the left-most side of the table rows with the defined form field for each row.
`$form_name` | No | string | Only required if the form_field variable is either "radio" or "checkbox".  Is the name of the form field.
`$form_value` | No | string | Only required if the "form_field" variable is set to either "radio" or "checkbox".  Is the value of the form field, as denoted within the database columns (usually "id").
`$delete_button` | No | string | If defined, a button with this text (eg. Delete Checked Rows) will be displayed at the bottom of the table.  If pressed, the user will have to confirm the deletion, then the software will automatically delete the checked rows from the appropriate database table, and remove them from the HTML table via AJAX.
`$delete_dbtable` | No | string | If a delete button is defined, the database table name to delete records from.  Defaults to PACKAGE_ALIAS of the table.
`$delete_dbcolumn` | No | string | If a delete button is defined, the column name from the database table to delete rows from.  Defaults to "id".


## Methods

Below describes all methods available within this class.


### `get_attributes(array $data)`

**Description:** Optional, and takes in the `$data` array, which is all attributes within the `<e:function>` tag that called the table.  This 
is useful for things such as show / hide columns, or retrieve only specific rows (ie. only gets rows pertaining to a specific user ID#).  For example:

~~~php
public function get_attributes(array $data) 
{

    // High 'status' column, if only displaying a single status
    if (isset($data['status'])) { unset($this->columns['status']); }

    // Set userid property, to later only retrieve rows pertaining to this specific user
    $this->userid = $data['user_id'];

}
~~~

In the above example, if the `<e:function>` tag already explicitly defines a `status` attribute, then we know only one status will be shown in this table, hence can hide the status 
column.  Plus it spects a `userid` attribute within the `<e:function>`, which we can use to only retrieve rows pertaining to that specific user account.


### `int get_total(string $search_term = '')`

**Description:** Returns an integer of the total number of rows within the database, and is used mainly for pagination.  
The `$search_term` variable passed is only if the <`$has_search` property is `true`, and the user has submitted the search.  This allows a different total to be gathered depending on the search term.



### `array get_rows(int $start = 0, string $search_term = '', string $order_by = 'id asc')`

**Description:** Retrieves the actual rows from the database to be displayed.  This method is used when initially 
displaying the table, plus during AJAX based pagination, search, and sort.  It returns an array of associative arrays that provide all row values to display to the browser.

**Parameters**

Parameter | Type | Notes
------------- |------------- |------------- 
`$start~ | int | Integer defining where in the dataset to start, and is used within the LIMIT clause of the SQL statement.  Defaults to 0, except for when pagination is being used, then is (page * rows_per_page).  For example, if rows per-page is 25, and viewing the 3rd page, this variable will be 50.
`$search_term` | string | Generally always blank, except for when the `$this->has_search` variable is set to 1, and the user is searching the dataset via AJAX.
`$order_by` | string | How to order the rows being retrieved, and is used within the ORDER BY clause of the SQL statement.  Always set this to the default order you'd like to display rows, and it will change as users click the up/down arrows within column headers for any sortable rows, as rows are being sroted via AJAX.

Within the default code, you will notice this is a fairly straight forward function.  The two separate SQL statements are only required if the `$has_search` property is set to `true`, otherwise you can just limit it to the one SQL statement.  The function simply retrieves the necessary rows from the database, then loops through them, putting each row through the `format_row()` method (see below), then returns the entire array of results.</p>


### `array format_row(array $row)`

**Description:** As rows are retrieved from the database, each row is passed through this method for formatting.  An array consisting of key-value pairs of one row from the database is passed, and you need to format it as necessary to be displayed to the web browser.  All the keys within the `$columns` property you defined need to be set within this function, and exactly how you want it to be displayed to the browser.

**NOTE:** Please format rows through this function only, and not through the `get_rows()` method, as this method can be used by other AJAX functions when dynamically adding / updating rows in a table.


