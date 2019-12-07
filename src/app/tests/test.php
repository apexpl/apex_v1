<?php
declare(strict_types = 1);

namespace apex\app\tests;

use apex\app;
use apex\svc\db;
use apex\svc\view;
use apex\app\sys\apex_cli;
use apex\users\user;
use apex\core\admin;
use apex\app\exceptions\ApexException;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_Constraint_IsEqual;


/**
 * Provides support for various custom assertions that can be executed within 
 * unit test classes.  Please refer to the developer documentation for full 
 * details on all custom assertions available within Apex. 
 */
class test   extends TestCase
{


    private $app;


/**
 * Some test method so phpUnit doesn't give off warnings. 
 */
public function test_junk()
{ 
    $this->assertTrue(true);
}

/**
 * Login 
 * 
 * @param string The area to login to (admin, members)
 * @param int $userid The ID# of the user to login as.
 */
public function login(string $area = 'members', int $userid = 0)
{ 
    // Login
    app::set_area($area);
    app::set_userid($userid);
}

/**
 * Conduct test HTTP request 
 *
 * Handle test request.  Used within the unit tests to emulate a HTTP request 
 * to the system, and obtain the response to check assertions. 
 *
 * @param string $uri The URI to send a request to
 * @param string $method The request method (POST or GET), defaults to GET
 * @param array $post Variables that should be POSTed to the URI
 * @param array $get Variables that should be included in GET / query string of URI
 * @param array $cookie Any cookie variables to include in test request (eg. auth hash if logged in)
 */
public function http_request(string $uri, string $method = 'GET', array $post = [], array $get = [], array $cookie = [])
{ 

    // Setup test request
    if (!$app = app::get_instance()) { 
        $app = new app('test');
    }
    $app->setup_test($uri, $method, $post, $get, $cookie);

    // Handle request
    app::call(["apex\\core\\controller\\http_requests\\" . app::get_http_controller(), 'process']);

    // Return response
    return app::get_res_body();

}



/**
 * Send a mock request to apex.php via CLI 
 */
public function send_cli(string $action, $vars = array())
{ 

    // Process action
    $response = app::call([apex_cli::class, $action], ['vars' => $vars]);

    // Return
    return $response;

}

/**
 * Invoke a protected / private method 
 *
 * @param mixed $object The object instance of the method
 * @param string $method_name The name of the method to call.
 * @param array $params The params to pass to the method.
 */
public function invoke_method($object, string $method_name, array $params = [])
{ 

    // Get method via reflection
    $reflection = new \ReflectionClass(get_class($object));
    $method = $reflection->getMethod($method_name);
    $method->setAccessible(true);

    // Call method, and return results
    return $method->invokeArgs($object, $params);

}

/**
 * Wait for an exception
 *
 * @param string $message Partial message that will be contained within exception
 */
public function waitException(string $message)
{

    // Expect exception
    $this->expectException(ApexException::class);
    $this->expectExceptionCode(500);
    $this->expectExceptionMessage($message);

}

/**
 * Get demo user / admin
 *
 * @Returns the ID# of the demo user / administrator, and if they don't exists, 
 * will automatically create them.
 * 
 * @param string $type The type of user to get, either 'user' or 'admin'.
 *
 * @return int the ID# of the user / admin.
 */
public function get_demo_user(string $type = 'user'):int
{

// Set variables
    if ($type == 'user') { 
        $table_name = 'users';
        $username = $_SERVER['apex_test_username'];
    } else { 
        $table_name = 'admin';
        $username = $_SERVER['apex_admin_username'];
    }

    // Check for existing user
    if ($userid = db::get_field("SELECT id FROM $table_name WHERE username = %s", $username)) { 
        return $userid;
    }

    // Set profile
    $profile = array(
        'username' => ($type == 'admin' ? $_SERVER['apex_admin_username'] : $_SERVER['apex_test_username']), 
        'password' => ($type == 'admin' ? $_SERVER['apex_admin_password'] : $_SERVER['apex_test_password']), 
        'password2' => ($type == 'admin' ? $_SERVER['apex_admin_password'] : $_SERVER['apex_test_password']), 
        'full_name' => 'Demo Account', 
        'first_name' => 'Demo', 
        'last_name' => 'Account', 
        'email' => 'demo@demo.com', 
        'phone_country' => '', 
        'phone' => '', 
        'require_2fa' => 0, 
        'require_2fa_phone' => 0, 
        'submit' => 'create'
    );

    // Update config, if needed
    if ($type == 'user') { 
        app::update_config_var('users:username_column', 'username');
    app::update_config_var('username:phone_verification', 2);
    app::update_config_var('users:email_verification', 2);
    }
    ////app::clear_cookie();

    // Create user
    $uri = $type == 'admin' ? 'admin/index' : '/register2';
    $html = $this->http_request($uri, 'POST', $profile);

    // Check for userid
    if (!$userid = db::get_field("SELECT id FROM $table_name WHERE username = %s", $username)) { 
        throw new ApexException('error', tr("Unable to create test {1} account with username {2}", $type, $username));
    }

    // Update bitcoin company_userid, if needed
    if ($type == 'user' && check_package('bitcoin') === true) { 
        app::update_config_var('bitcoin:company_userid', $userid);
    }

    // Return
    return $userid;

}

/**
 * Check page title 
 *
 * Check title of most recently requested page to see if it equals expected 
 * title. Assertions:   assertpagetitle / assertnotpagetitle 
 *
 * @param string $title The page of the page that must match
 */
final public function assertPageTitle(string $title) { $this->checkPageTitle($title, true); }

/**
 * @Ignore
 */
final public function assertNotPageTitle(string $title) { $this->checkPageTitle($title, false); }

/**
 * @Ignore
 */
private function checkPageTitle(string $title, bool $has = true)
{ 

    // Assert
    $chk_title = view::get_title();
    if ($has === true) { 
        $this->assertEquals($title, $chk_title, tr("Title of page at {1}/{2} does NOT equal the title: {3}", app::get_area(), app::get_uri(), $title));
    } else { 
        $this->assertNotEquals($title, $chk_title, tr("Title of page at {1}/{2} does equal the title: {3}", app::get_area(), app::get_uri(), $title));
    }

}

/**
 * Check if page title contains 
 *
 * Check if the title of the most recently requested page contains a string of 
 * text. Assertions:  assertpagetitlecontains / assertpagetitlenotcontains 
 *
 * @param string $text The string of text to check the page title for.
 */
final public function assertPageTitleContains(string $text) { $this->checkPageTitleContains($text, true); }

/**
 * @Ignore
 */
final public function assertPageTitleNotContains(string $text) { $this->checkPageTitleContains($text, false); }

/**
 * @Ignore
 */
private function checkPageTitleContains(string $text, bool $has = true)
{ 

    // Assert
    $ok = strpos(view::get_title(), $text) === false ? false : true;
    if ($ok !== $has) { 
        $not = $has === true ? ' NOT ' : array();
        $this->asserttrue(false, tr("Title of page {1}/{2} does $not contain the text: {3}", app::get_area(), app::get_uri(), $text));
    } else { 
        $this->asserttrue(true);
    }

}

/**
 * Check if page contents contains text 
 *
 * Check if the most recently requested page contains a string of text. 
 * Assertions:   assertpagecontains / assertpagenotcontains 
 *
 * @param string $text The string of text to check if the page contains.
 */
final public function assertPageContains(string $text) { $this->checkPageContains($text, true); }

/**
 * @Ignore
 */
final public function assertPageNotContains(string $text) { $this->checkPageContains($text, false); }

/**
 * @Ignore
 */
private function checkPageContains(string $text, bool $has = true)
{ 

    // Check
    $ok = strpos(app::get_res_body(), $text) === false ? false : true;

    // Assert
    if ($ok !== $has) { 
        $not = $has === true ? ' NOT ' : '';
        $this->asserttrue(false, tr("The page {1}/{2} does $not contain the text {3}", app::get_area(), app::get_uri(), $text));
    } else { 
        $this->asserttrue(true);
    }

}

/**
 * Check if contains callout 
 *
 * Check if most recent page requested contains user message / callout of 
 * specified type and contains specified text. Assertions: 
 * asserthasusermessage / assertnothasusermessage 
 *
 * @param string $type The type of message (success, error, info, warning)
 * @param string $text The text should one of the messages should contain
 */
final public function assertHasCallout($type = 'success', $text = '') { $this->checkHasCallout($type, $text, true); }

/**
 * @Ignore
 */
final public function assertNotHasCallout($type = 'success', $text = '') { $this->checkHasCallout($type, $text, true); }

/**
 * @Ignore
 */
private function checkHasCallout(string $type, string $text = '', bool $has = true)
{ 

    // Get the messages to check
    $msg = view::get_callouts();
    if (!isset($msg[$type])) { $msg[$type] = array(); }

    // Check message type
    $found = count($msg[$type]) > 0 && $text == '' ? true : false;

    // Check for text, if needed
    if ($text != '') { 
        foreach ($msg[$type] as $message) { 
            if (strpos($message, $text) !== false) { $found = true; }
        }

        // Ensure it appears on page
        if (strpos(app::get_res_body(), $text) === false) { $found = false; }
    }

    // Assert
    if ($found !== $has) { 
        $not = $has === true ? ' NOT ' : '';
        $this->asserttrue(false, tr("The page {1}/{2} does $not contain a user message of type {3} that contains the text: {4}", app::get_area(), app::get_uri(), $type, $text));
    } else { 
        $this->asserttrue(true);
    }

}

/**
 * Check form validation error 
 *
 * Checks the most recently requested page to see whether or not it contains 
 * the specified form validation error.  These are the validation errors given 
 * off by the forms::validate_form() method. Assertions:  asserthasformerror / 
 * assertnothasformerror 
 *
 * @param string $type The type of validation error (blank, email, alphanum)
 * @param string $name The name of the form field to check.
 */
final public function assertHasFormError(string $type, string $name) { $this->checkHasFormError($type, $name, true); }

/**
 * @Ignore
 */
final public function assertNotHasFormError(string $type, string $name) { $this->checkHasFormError($type, $name, false); }

/**
 * @Ignore
 */
private function checkHasFormError(string $type, string $name, bool $has = true)
{ 

    // Set variables
    $name = ucwords(str_replace("_", " ", $name));
    $errors = view::get_callouts()['error'] ?? array();

    // Create message
    if ($type == 'blank') { $msg = "The form field $name was left blank, and is required"; }
    elseif ($type == 'email') { $msg = "The form field $name must be a valid e-mail address."; }
        elseif ($type == 'alphanum') { $msg = "The form field $name must be alpha-numeric, and can not contain spaces or special characters."; }
        elseif ($type == 'decimal') { $msg = "The form field $name can only be a decimal / amount."; }
    elseif ($type == 'alphanum') { $msg = "The form field $name must be alpha-numeric, and can not contain spaces or special characters."; }
    else { return; }

    // Check messages
    $found = false;
    foreach ($errors as $message) { 
        if (strpos($msg, $message) !== false) { $found = true; }
    }

    // Assert
    if ($found !== $has) { 
        $not = $has === true ? ' NOT ' : '';
        $this->asserttrue(false, tr("The page {1}/{2} does $not contain a form error of type: {3} for the form field: {4}", app::get_area(), app::get_uri(), $type, $name));
    } else { 
        $this->asserttrue(true);
    }

}

/**
 * Check if contains <hX> tag 
 *
 * Check if the page contains the specified <hX> tag with the specified text. 
 * Assertions:  asserthasheading / assertnothasheading 
 *
 * @param int $hnum The heading number (1 - 6)
 * @param string $text The text to check for
 */
final public function assertHasHeading($hnum, string $text) { $this->checkHasHeading($hnum, $text, true); }

/**
 * @Ignore
 */
final public function assertNotHasHeading($hnum, string $text) { $this->checkHasHeading($hnum, $text, false); }

/**
 * @Ignore
 */
private function checkHasHeading($hnum, string $text, bool $has = true)
{ 

    // Check for heading
    $found = false;
    preg_match_all("/<h" . $hnum . ">(.*?)<\/h" . $hnum . ">/si", app::get_res_body(), $hmatch, PREG_SET_ORDER);
    foreach ($hmatch as $match) { 
        if ($match[1] == $text) { $found = true; }
    }

    // Assert
    if ($found !== $has) { 
        $not = $has === true ? ' NOT ' : '';
        $this->asserttrue(false, tr("The page {1}/{2} does $not contain a heading of h{3} with the text: {4}", app::get_area(), app::get_uri(), $hnum, $text));
    } else { 
        $this->asserttrue(true);
    }

}

/**
 * Check for submit button 
 *
 * Check whether or not most recent page requested contains a submit button 
 * with specified value and label. Assertions:  asserthassubmit / 
 * assertnothassubmit 
 *
 * @param string $value The value of the submit button
 * @param string $label The label of the submit button (what is shown in the web browser)
 */
final public function assertHasSubmit(string $value, string $label) { $this->checkHasSubmit($value, $label, true); }

/**
 * @Ignore
 */
final public function assertNotHasSubmit(string $value, string $label) { $this->checkHasSubmit($value, $label, false); }

/**
 * @Ignore
 */
private function checkHasSubmit(string $value, string $label, $has = true)
{ 

    // Set variables
    $html = app::get_res_body();
    $chk = "<button type=\"submit\" name=\"submit\" value=\"$value\" class=\"btn btn-primary btn-lg\">$label</button>";

    // Assert
    $ok = strpos($html, $chk) === false ? false : true;
    if ($ok !== $has) { 
        $word = $has === true ? ' NOT ' : '';
        $this->asserttrue(false, "The page does $word contain a submit button with the value: $value, and label: $label");
    } else { 
        $this->asserttrue(true);
    }

}

/**
 * Check for table component 
 *
 * Check if most recently requested page contains a specific HTML table that 
 * is displayed via the <e:function> tag. Assertions:  asserthastable / 
 * assertnothastable 
 *
 * @param string $Table_alias The alias of the table in Apex format (ie. PACKAGE:ALIAS)
 */
final public function assertHasTable(string $table_alias) { $this->checkHasTable($table_alias, true); }

/**
 * @Ignore
 */
final public function assertNotHasTable(string $table_alias) { $this->checkHasTable($table_alias, false); }

/**
 * @Ignore
 */
private function checkHasTable(string $table_alias, bool $has = true)
{ 

    // Set variables
    $html = app::get_res_body();
    $chk = 'tbl_' . str_replace(":", "_", $table_alias);

    // GO through all tables on page
    $found = false;
    preg_match_all("/<table(.*?)>/si", $html, $table_match, PREG_SET_ORDER);
    foreach ($table_match as $match) { 
        $attr = view::parse_attr($match[1]);
        $id = $attr['id'] ?? '';
        if ($id == $chk) { $found = true; }
    }

    // Assert
    if ($found !== $has) { 
        $not = $has === true ? ' NOT ' : '';
        $this->asserttrue(false, tr("The page {1}/{2} does $not contain a table with the alias: {3}", app::get_area(), app::get_uri(), $table_alias));
    } else { 
        $this->asserttrue(true);
    }

}

/**
 * Check table rows for specific column / value 
 *
 * Check all rows within a HTML tab and see if a column contains specified 
 * value. Assertions:asserthastablefield / assertnothastablefield 
 *
 * @param string $table_alias The alias of the HTML tab in Apex format (ie. PACKAGE:ALIAS)
 * @param int $col_num The number of the column, 0 being the left most column
 * @param string $value The value to check the column for.
 */
final public function assertHasTableField(string $table_alias, int $col_num, string $value) { $this->checkHasTableField($table_alias, $col_num, $value, true); }

/**
 * @Ignore
 */
final public function assertNotHasTableField(string $table_alias, int $col_num, string $value) { $this->checkHasTableField($table_alias, $col_num, $value, false); }

/**
 * @Ignore
 */
private function checkHasTableField(string $table_alias, int $column_num, string $value, bool $has = true)
{ 

    // Set variables
    $html = app::get_res_body();
    $table_alias = 'tbl_' . str_replace(":", "_", $table_alias);

    // Go through tables
    $found = false;
    preg_match_all("/<table(.+?)>(.*?)<\/table>/si", $html, $table_match, PREG_SET_ORDER);
    foreach ($table_match as $match) { 

        // Check table ID
        $attr = view::parse_attr($match[1]);
        $id = $attr['id'] ?? '';
        if ($id != $table_alias) { continue; }

        // Get tbody contents
        if (!preg_match("/<tbody(.*?)>(.*?)<\/tbody>/si", $match[2], $tbody)) { 
            continue;
        }

        // Go through all rows
        preg_match_all("/<tr>(.*?)<\/tr>/si", $tbody[2], $row_match, PREG_SET_ORDER);
        foreach ($row_match as $row) { 

            // Go through cells
            preg_match_all("/<td(.*?)>(.*?)<\/td>/si", $row[1], $cell_match, PREG_SET_ORDER);
            $chk = $cell_match[$column_num][2] ?? '';

            if ($chk == $value) { 
                $found = true;
                break;
            }

        }
        if ($found === true) { break; }

    }

    // Assert
    if ($found !== $has) { 
        $not = $has === true ? ' NOT ' : '';
        $this->asserttrue(false, tr("On the page {1}/{2} the table with alias {3} does $not have a row that contains the text {4} on column number {5}", app::get_area(), app::get_uri(), $table_alias, $value, $column_num));
    } else { 
        $this->asserttrue(true);
    }

}

/**
 * Check if database row exists 
 *
 * Checks if a row exists within the database with the specified SQL 
 * statement. Assertions:  asserthasdbrow / assertnothasdbrow 
 *
 * @param string $sql The SQL statement to check if row exists
 */
final public function assertHasDBRow(string $sql) { $this->checkHasDBRow($sql, true); }

/**
 * @Ignore
 */
final public function assertNothasDBRow(string $sql) { $this->checkHasDBRow($sql, false); }

/**
 * @Ignore
 */
private function checkHasDBRow(string $sql, bool $has = true)
{ 

    // Assert
    $row = db::get_row($sql);
    if (($row === false && $has === true) || (is_array($row) && $has === false)) { 
        $not = $has === true ? ' NOT ' : '';
        $this->asserttrue(false, "Database row does $not exist for the SQL statement, $sql");
    } else { 
        $this->asserttrue(true);
    }

}

/**
 * Check value of single database field 
 *
 * Retrieve one row from the mySQL database using a SQL query, and check to 
 * ensure the row exists, and contains a column with the specified value. 
 * Assertions:   asserthasdbfield / assertnothasdbfield 
 *
 * @param string $sql The SQL query to perform to retrieve the one row
 * @param string $column The name of the column to check.
 * @param string $value The value the column name should match
 */
final public function assertHasDBField(string $sql, string $column, string $value) { $this->checkHasDBField($sql, $column, $value, true); }

/**
 * @Ignore
 */
final public function assertNotHasDBField(string $sql, string $column, string $value) { $this->checkHasDBField($sql, $column, $value, false); }

/**
 * @Ignore
 */
private function checkHasDBField(string $sql, string $column, string $value, bool $has = true)
{ 

    // Perform check
    $ok = false;
    if ($row = db::get_row($sql)) { 
        $ok = isset($row[$column]) && $row[$column] == $value ? true : false;
    }

    // Assert
    if ($ok !== $has) { 
        $not = $has === true ? ' NOT ' : '';
        $this->asserttrue(false, tr("Database row does $not contain a column with the name {1} with the value {2}, retrived from the SQL query: {3}", $column, $value, $sql));
    } else { 
        $this->asserttrue(true);
    }

}

/**
 * Check if form field exists 
 *
 * Check if the most recently requested page contains a form field with the 
 * specified name. Assertions:   asserthasformfield / assertnothasformfield 
 *
 * @param string $name The name of the form field.
 */
final public function assertHasFormField($name) { $this->checkHasFormField($name, true); }

/**
 * @Ignore
 */
final public function assertNotHasFormField($name) { $this->checkHasFormField($name, false); }

/**
 * @Ignore
 */
private function checkHasFormField($name, bool $has = true)
{ 

    // Get names
    $fields = is_array($name) ? $name : array($name);


    // Get HTML
    $html = app::get_res_body();

    // Go through fields
    foreach ($fields as $name) { 

        // Go through form fields
        $found = false;
        preg_match_all("/<input(.*?)>/si", $html, $field_match, PREG_SET_ORDER);
        foreach ($field_match as $match) { 
            $attr = view::parse_attr($match[1]);
            if (isset($attr['name']) && $attr['name'] == $name) { 
                $found = true;
                break;
            }
        }

        // Go through select lists
        preg_match_all("/<select(.*?)>/si", $html, $select_match, PREG_SET_ORDER);
        foreach ($select_match as $match) { 
            $attr = view::parse_attr($match[1]);
            if (isset($attr['name']) && $attr['name'] == $name) { 
                $found = true;
                break;
            }
        }

        // Assert
        if ($found !== $has) { 
            $not = $has === true ? ' NOT ' : '';
            $this->asserttrue(false, tr("Page at {1}/{2} does $not contain a form field with the name {3}", app::get_area(), app::get_uri(), $name));
        } else { 
            $this->asserttrue(true);
        }

    }

}

/**
 * String contains text 
 *
 * Assert a string contains certain text Assertions:  assertstringcontains / 
 * assertstringnotcontains 
 *
 * @param string $string The string
 * @param string $text The text to see if it's contained within the string
 */
final public function assertStringContains(string $string, string $text) { $this->checkStringContains($string, $text, true); }

/**
 * @Ignore
 */
final public function assertStringNotContains(string $string, string $text) { $this->checkStringContains($string, $text, false); }

/**
 * @Ignore
 */
private function checkStringContains(string $string, string $text, bool $has = true)
{ 

    // Check
    $ok = strpos($string, $text) === false ? false : true;
    if ($ok !== $has) { 
        $not = $has === true ? ' NOT ' : '';
        $this->asserttrue(false, tr("The provided string does $not contain the text: {1}", $text));
    } else { 
        $this->asserttrue(true);
    }

}

/**
 * Check file contains text 
 *
 * Check that the contents of a file contains the specified text. Assertions: 
 * assertfilecontains / assertfilenotcontains 
 *
 * @param string $file The filename to check
 * @param string $text The text to check if the file contents contains
 */
final public function assertFileContains(string $filename, string $text) { $this->checkFileContains($filename, $text, true); }
/**
 * @Ignore
 */
final public function assertFileNotContains(string $filename, string $text) { $this->checkFileContains($filename, $text, false); }

/**
 * @Ignore
 */
private function checkFileContains(string $filename, string $text, bool $has = true)
{ 

    // Check
    $ok = false;
    if (file_exists($filename)) { 
        $contents = file_get_contents($filename);
        if ($contents == '' || $text == '') { 
            $ok = true;
        } else { 
            $ok = strpos($contents, $text) === false ? false : true;
        }
    }

    // Assert
    if ($ok !== $has) { 
        $not = $has === true ? ' NOT ' : '';
        $this->asserttrue(false, tr("The file {1} does $not contain the text: {2}", $filename, $text));
    } else { 
        $this->asserttrue(true);
    }

}


}

