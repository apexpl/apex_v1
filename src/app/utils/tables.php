<?php
declare(strict_types = 1);

namespace apex\app\utils;

use apex\app;
use apex\libc\debug;


/**
 * Some misceallensous functions that are required for the data table 
 * component to function properly, mainly just obtaining full details on the 
 * table being displayed such a total rows, current page, etc. 
 */
class tables
{




/**
 * Get details of a data table component, such as total pages, the rows to 
 * display, currnet page, etc. 
 *
 * @param object $table The 'apex\abstracts\table' object of the desired table component.
 * @param string $table_id The element ID of the table within the browser.
 *
 * @return array An array containg the basic details of the table / page
 */
public function get_details($table, string $table_id = ''):array
{ 

    // Debug
    debug::add(4, tr("Getting details of data table, {1}", $table));

    // Set variables
    $page = app::_post('page') ?? 1;
    $search_term = app::_post('search_' . $table_id) ?? '';
    $order_by = app::has_post('sort_col') && app::has_post('sort_dir') ? app::_post('sort_col') . ' ' . app::_post('sort_dir') : '';

    // Get total rows
    $total = method_exists($table, 'get_total') ? $table->get_total($search_term) : 0;
    $rows_per_page = $table->rows_per_page ?? 0;
    $has_pages = $total == 0 || $rows_per_page == 0 ? false : true;

    // Get page details
    if ($has_pages === true) { 

        // Get total pages, and start #
        $total_pages = ceil($total / $rows_per_page);
        $start = $page == 1 ? 0 : ($page - 1) * $rows_per_page;

        // Get start / end page
        $start_page = ($page > 5) ? ($page - 5) : 1;
        $end_page = ($page < 5) ? ((11 - $page) + $page) : ($page + 5);
        if ($end_page > $total_pages) { $end_page = $total_pages; }

    } else { list($start, $total_pages, $start_page, $end_page) = array(0, 0, 0, 0); }

    // Get rows
    $rows = $order_by == '' ? $table->get_rows($start, $search_term) : $table->get_rows($start, $search_term, $order_by);

    // Set return vars
    $vars = array(
        'has_pages' => $has_pages,
        'page' => $page,
        'start' => $start,
        'total' => $total,
        'rows_per_page' => $rows_per_page,
        'total_pages' => $total_pages,
        'start_page' => $start_page,
        'end_page' => $end_page,
        'rows' => $rows
    );

    // Return
    return $vars;

}


}

