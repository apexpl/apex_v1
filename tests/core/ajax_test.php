<?php
declare(strict_types = 1);

namespace tests\core;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\app\web\ajax;
use apex\app\tests\test;


/**
 * Handles all unit tests for AJAX functions within the 
 * /src/core/ajax/ directory, plus the main AJAX library 
 * located at /src/app/web/ajax.php
 */ 
class test_ajax extends test
{

/**
 * setUp
 */
public function setUp():void
{

    // Get app
    if (!$app = app::get_instance()) { 
        $app = new app('test');
    }

}

/**
 * tearDown
 */
public function tearDown():void
{

}

/**
 * No function alias -- give exception
 */
public function test_ajax_no_function_alias()
{

    $this->waitException('Invalid request');
    $this->http_request('ajax', 'POST');

}

/**
 * Ajax request - invalid alias
 */
public function test_ajax_invalid_alias()
{

    // Get exception
    $this->waitException('does not exist');
    $this->http_request('ajax/core/some_junk_function', 'POST');

}


/**
 * Ajax -- Navigate table
 */
public function test_ajax_navigate_table()
{

    // Set request
    $request = array(
        'table' => 'core:admin', 
        'id' => 'tbl_core_admin', 
        'page' => 1
    );

    // Send http request
    $response = $this->http_request('ajax/core/navigate_table', 'POST', $request);
    $actions = $this->check_json($response);
    $this->check_action($actions, 'clear_table', array('divid' => 'tbl_core_admin'));
    $this->check_action($actions, 'add_data_row', array('divid' => 'tbl_core_admin'));

}

/**
 * navigate_table -- No table exists
 */
public function test_ajax_navigate_table_not_exists()
{

    // Set request
    $request = array(
        'table' => 'core:some_junk_table', 
        'id' => 'tbl_core_some_junk_table', 
        'page' => 1
    );

    // Get exception
    $this->waitException('does not exist');
    $this->http_request('ajax/core/navigate_table', 'POST', $request);

}

/**
 * delete_rows
 */
public function test_ajax_delete_rows()
{

    // Set request
    $request = array(
        'table' => 'core:notifications', 
        'id' => 'tbl_core_notifications', 
        'notification_id' => array(8583163)
    );

    // Send http request
$response = $this->http_request('ajax/core/delete_rows', 'POST', $request);
    $actions = $this->check_json($response);
    $this->check_action($actions, 'remove_checked_rows', array('divid' => 'tbl_core_notifications'));

}

/**
 * delete_rows -- not_exists
 */
public function test_ajax_delete_rows_not_exists()
{

    // Set request
    $request = array(
        'table' => 'core:junk_table_never_exist', 
        'id' => 'tbl_core_junk_table_never_exist', 
    );

    // Get exception
    $this->waitException('does not exist');
    $response = $this->http_request('ajax/core/delete_rows', 'POST', $request);

}

/**
 * search_autosuggest
 */
public function test_ajax_search_autosuggest()
{

    // Set request
    $request = array(
        'autosuggest' => 'users:find', 
        'term' => 'demo'
    );

    // Send http request
    $response = $this->http_request('ajax/core/search_autosuggest', 'GET', array(), $request);
    $this->assertNotEmpty($response);

    // Check JSON
    $vars = json_decode($response, true);
    $this->assertIsArray($vars);
    $this->assertArrayHasKey('label', $vars[0]);
    $this->assertArrayHasKey('data', $vars[0]);

}

/**
 * search_autosuggest - not_exists
 */
public function test_search_autosuggest_not_exists()
{

    // Set request
    $request = array(
        'autosuggest' => 'core:junk_autosuggest', 
        'term' => 'junk'
    );

    // Get exception
    $this->waitException('does not exist');
    $response = $this->http_request('ajax/core/search_autosuggest', 'GET', array(), $request);

}

/**
 * Search table
 */
public function test_ajax_search_table()
{

    // Set request
    $request = array(
        'table' => 'core:packages', 
        'id' => 'tbl_core_packages' 
    );

    // CHeck for no search term
    $response = $this->http_request('ajax/core/search_table', 'POST', $request);
    $actions = $this->check_json($response);
    $this->check_action($actions, 'alert', array('message' => 'You did not specify any text to search for.'));

    // Send http request
    $request['search_tbl_core_packages'] = 'Core';
    $response = $this->http_request('ajax/core/search_table', 'POST', $request);
    $actions = $this->check_json($response);
    $this->check_action($actions, 'clear_table', array('divid' => 'tbl_core_packages'));
    $this->check_action($actions, 'add_data_row', array('divid' => 'tbl_core_packages'));

}

/**
 * search_table - not exists
 */
public function test_ajax_search_table_not_exists()
{

    // Set request
    $request = array(
        'table' => 'core:junk', 
        'id' => 'tbl_core_junk', 
        'search_tbl_core_junk' => 'junk'
    );

    // Get exception
    $this->waitException('does not exist');
    $this->http_request('ajax/core/search_table', 'POST', $request);

}

/**
 * sort_table
 */
public function test_ajax_sort_table()
{

    // Set request
    $request = array(
        'table' => 'core:admin', 
        'id' => 'tbl_core_admin', 
        'sort_dir' => 'desc', 
        'sort_col' => 'username'
    );

    // Send http request
    $response = $this->http_request('ajax/core/sort_table', 'POST', $request);
    $actions = $this->check_json($response);
    $this->check_action($actions, 'clear_table', array('divid' => 'tbl_core_admin'));
    $this->check_action($actions, 'add_data_row', array('divid' => 'tbl_core_admin'));

}

/**
 * sort_table -- not_exists
 */
public function test_ajax_sort_table_not_exists()
{

    // Set request
    $request = array(
        'table' => 'core:junk', 
        'id' => 'tbl_core_junk', 
        'sort_dir' => 'desc', 
        'sort_col' => 'username'
    );

    // Get exception
    $this->waitException('does not exist');
    $response = $this->http_request('ajax/core/sort_table', 'POST', $request);

}

/**
 * Check JSOn response
 */
public function check_json($response)
{

    // Check response
    $this->assertNotEmpty($response); 
    $vars = json_decode($response, true);
    $this->assertIsArray($vars);
    $this->assertArrayHasKey('status', $vars);
    $this->assertEquals('ok', $vars['status']);
    $this->assertIsArray($vars['actions']);

    // Return
    return $vars['actions'];


}

/**
 * Check actions array
 */
private function check_action(array $actions, $action, array $attrs = [])
{

    // Initialize
    $found = false;
    $found_attr = [];

    // Go through the actions
    foreach ($actions as $vars) { 
        if (!isset($vars['action'])) { continue; }
        if ($vars['action'] != $action) { continue; }

        $found = true;
        foreach ($attrs as $key => $value) { 
            $found_attrs[$key] = isset($vars[$key]) && $vars[$key] == $value ? true : false;
        }
    }

    // Assert
    $this->assertTrue($found, "Unable to find action in JSON response, $action");
    foreach ($found_attrs as $key => $ok) { 
        $this->assertTrue($ok, "Unable to find attribute of $key with value $attrs[$key] within actions JSON");
    }

}

/**
 * Process
 */
public function test_process()
{

    $client = app::make(ajax::class);
    $ok = $client->process();
    $this->assertTrue(true);

}

/**
 * add_data_row -- invalid talbe alias
 */
public function test_add_data_row_invalid_table_alias()
{

    $this->waitException('does not exist');
    $client = app::make(ajax::class);
    $client->add_data_rows('tbl_junk', 'core:some_junk_and_invalid_table', array()); 

}

/**
 * append
 */
public function test_append()
{

    $client = app::make(ajax::class);
    $client->append('test_div', 'unit_Test');
    $client->clear_list('test_list');
    $this->assertTrue(true);

}

}


