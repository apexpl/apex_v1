
# Apex - Create Data Table

We will want a data table to display the previous winners of all daily lotters, plus any lotters the user themselves has won while they 
are logged into the member's area.  Within terminal, type:

`./apex create table training:lotteries`

This will create a new file at */src/training/table/lotteries.php*.  Open this file, and enter the following contents:

~~~php
<?php
declare(strict_types = 1);

namespace apex\training\table;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\users\user;

/**
 * Table for lotteries
 */
class lotteries
{

    // Columns
    public $columns = array(
        'date_added' => 'Date', 
        'username' => 'User', 
        'amount' => 'Amount', 
        'chances' => 'Chances'
    );

    // Sortable columns
    public $sortable = array('date_added');

    // Other variables
    public $rows_per_page = 25;
    public $has_search = false;

    // Form field (left-most column)
    public $form_field = 'checkbox';
    public $form_name = 'lottery_id';
    public $form_value = 'id'; 

    // Delete button
    public $delete_button = 'Delete Checked Lotteries';
    public $delete_dbtable = 'lotteries';
    public $delete_dbcolumn = 'id';

/**
 * Construct
 */
public function __construct()
{

    if (app::get_area() != 'admin') { 
        $this->form_field = 'none';
        $this->delete_button = '';
    }

}


/**
 * Parse attributes within <a:function> tag.
 *
 * Passes the attributes contained within the <e:function> tag that called the table.
 * Used mainly to show/hide columns, and retrieve subsets of 
 * data (eg. specific records for a user ID#).
 * 
 (     @param array $data The attributes contained within the <e:function> tag that called the table.
 */
public function get_attributes(array $data = array())
{

    $this->userid = $data['userid'] ?? 0;

}

/**
 * Get total rows.
 *
 * Get the total number of rows available for this table.
 * This is used to determine pagination links.
 * 
 *     @param string $search_term Only applicable if the AJAX search box has been submitted, and is the term being searched for.
 *     @return int The total number of rows available for this table.
 */
public function get_total(string $search_term = ''):int 
{

    // Get total
    if ($this->userid > 0) { 
        $total = db::get_field("SELECT count(*) FROM lotteries WHERE userid = %i", $this->userid);
    } else { 
        $total = db::get_field("SELECT count(*) FROM lotteries");
    }
    if ($total == '') { $total = 0; }

    // Return
    return (int) $total;

}

/**
 * Get rows to display
 *
 * Gets the actual rows to display to the web browser.
 * Used for when initially displaying the table, plus AJAX based search, 
 * sort, and pagination.
 *
 *     @param int $start The number to start retrieving rows at, used within the LIMIT clause of the SQL statement.
 *     @param string $search_term Only applicable if the AJAX based search base is submitted, and is the term being searched form.
 *     @param string $order_by Must have a default value, but changes when the sort arrows in column headers are clicked.  Used within the ORDER BY clause in the SQL statement.
 *
 *     @return array An array of associative arrays giving key-value pairs of the rows to display.
 */
public function get_rows(int $start = 0, string $search_term = '', string $order_by = 'id asc'):array 
{

    // Get rows
    if ($this->userid > 0) { 
        $rows = DB::query("SELECT * FROM lotteries WHERE userid = %i ORDER BY $order_by LIMIT $start,$this->rows_per_page", $this->userid);
    } else { 
        $rows = DB::query("SELECT * FROM lotteries ORDER BY $order_by LIMIT $start,$this->rows_per_page");
    }

    // Go through rows
    $results = array();
    foreach ($rows as $row) { 
        array_push($results, $this->format_row($row));
    }

    // Return
    return $results;

}

/**
 * Format a single row.
 *
 * Retrieves raw data from the database, which must be 
 * formatted into user readable format (eg. format amounts, dates, etc.).
 *
 *     @param array $row The row from the database.
 *
 *     @return array The resulting array that should be displayed to the browser.
 */
public function format_row(array $row):array 
{

    // Load user
    $user = app::make(user::class, ['id' => (int) $row['userid']]);
    $profile = $user->load();

    // Format row
    $row['username'] = $profile['username'];
    $row['amount'] = fmoney((float) $row['amount']);
    $row['date_added'] = fdate($row['date_added']);
    $row['chances'] = '1 of ' . $row['total_entries'];


    // Return
    return $row;

}

}

~~~

If you look within the properties you will see the `$columns` array, which defines the various columns within our 
data table.  The other properties should be quite self explanatory, and then you will see the few methods within the class to 
get the total amount of rows (for pagination), the actual rows to display for a given request, and one method to format a row for display in the web browser.  For full 
information on data tables, please visit the [Data Table Component](../components/table.md) page of the documentation.


### Next

Last up, let's quickly finish our two other views for the menus we defined, and start with the 
[Member Area View](member_area_view.md).



