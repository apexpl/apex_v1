
# Testing via phpUnit

Apex fully integrates with the popular phpUnit to allow for unit tests.  If you are not currently familiar
with phpUnit, please take a look at the below two links to help familiarize yourself with it.

* [Getting Started with phpUnit](https://phpunit.de/getting-started/phpunit-7.html)
* [phpUnit Assertions](https://phpunit.readthedocs.io/en/8.0/assertions.html)

*NOTE:* When running unit tests, you will probably want to modify the /phpunit.xml file, line 13, and change the directory to your package 
directory of /tests/PACKAGE_ALIAS.  This will help avoid any errors / failures due to misconfiguration with regards to testing the other packages.

Below contains links to the sections within this page:

1. <a href="#creating_tests">Creating / Executing Test Classes</a>
2. <a href="#http_request">http_request($uri, $method, $post, $get, $cookie)</a>
3. <a href="#invoke_method">invoke_method($object, $method, $params)</a>
4. <a href="#auto_login">auto_login()</a>
5. <a href="#testing_emails">Testing E-Mail messages</a>
6. <a href="#custom_assertions">Custom Assertions</a>



<a name="creating_tests"></a>
### Creating / Executing Test Classes

Create a new test class by simply opening up terminal, change to the installation directory, and type: `php
apex.php create test PACKAGE:ALIAS`

This will create a file at */tests/PACKAGE/ALIAS*.php which you may modify as needed, and add your test
methods which will be executed by phpUnit.  You can then automatically run the unit tests with:

`./vendor/bin/phpunit`


<a name="http_request"></a>
### string $this->http_request($uri, [$method = 'GET'], [array $post = array()], [array $get = array()], [array $cookie = array()])

**Description:** A very useful method you will probably find yourself using often while writing your unit
tests.  This will emulate a HTTP request to any page within the software, and return the response.  Useful to
allow the unit tests to emulate a human being going through the online operation.

**Parameters**

Variable | Type | Description 
------------- |------------- |------------- 
`$uri` | string | The URI which to request (eg. /register, /admin/settings/mypackage, etc.) 
`$method` | string | Should always be either GET or POST, and is the request method of the request. Defaults to GET. 
`$post` | array | Array containing any variables you would like POSTed to the request. 
`$get` | array | Array of any GET values you would like included within the request (ie. the query string) 
`$cookie` | array | Optional array of any cookie key-value pairs you would like included within the request.


**Example**

~~~php
namespace tests\mypackage;

use apex\app;
use apex\app\tests\test;

class test_myclass extends test
{

/**
 * Test a login
 */
public function test_login()
{

    // Set post varaibles
    $request = array(
        'username' => 'myuser',
        'password' => 'mypass',
        'submit' => 'login'
    );

    // Send request
    $html = $this->http_request('/login', 'POST', $request);
    $this->assertPageTitle("Welcome to the member's area");

}

}
~~~

The above example will send a POST request to /login on the system, emulating a user submitting the public
login form.


<a name="invoke_method"></a>
### mixed $this->invoke_methoe($object, string $method, array $params = array())

**Description:** Allows you to access protected / private methods within your classes for testing purposes.

**Parameters**

Variable | Type | Description 
------------- |------------- |------------- 
`$object` | object | An instance of the object where the method resides. 
`$method` | string | The name of the method to call. 
`$params` | array | Optional name based array of params to pass to the method.

**Example**

~~~php
function test_sometest()
{

    // Call the get_password() method of the reseller class, which is a private method
    $reseller = new reseller();
    $pass = $this->invoke_method($reseller, 'get_password', array('userid' => 54));
}
~~~



<a name="auto_login"></a>
### auth::auto_login(int $userid)

**Description:** Useful to auto-login a user to the administration panel or member's area for testing purposes.  If logging into the 
administration panel, you must first set the area to "admin" via the app::set_area($area) method.  You only need to login 
once per-session, as the authenticated session will remain within all tests once first initiated.

**Example**

~~~php
function test_something()
{

    // Login admin ID# 1
    app::set_area('admi');
    auth::auto_login(1);

    // Do the tests ///
}
~~~


<a name="testing_emails"></a>
### Testing E-Mail Messages

While executing unit tests, Apex will store all outgoing e-mail messages in a queue, allowing you to easily lookup and test 
to ensure e-mail messages were sent out and formatted properly.  For full information, please visit the [Testing E-Mail Messages](tests_emails.md) page.


<a name="custom_assertions"></a>
### Custom Assertions

On top of all the standard assertions provided by phpUnit, Apex also offers various additional assertions to
help aid in the writing of unit tests.  These can all be executed exactly as phpUnit assertions, such as for
example within one of your test classes you could use:

~~~php
function test_sometest()
{

    // Send request
    $html = $this->http_request('/admin/casinos/games');

    // Check the page title
    $this->assertPageTitle('Manage Casino Games');

    // Check for a callout
    $this->assertHasCallout('success', "Successfully created new casino game");
~~~

The below table lists all custom assertions available to your unit tests within Apex.


Method | Description 
------------- |------------- 
`assertPageTitle($title)` | Checks the page title of the last test request sent to see if it matches `$title`.  The inverse is `assertNotPageTitle`.
`assertPageTitleContains($text)` | Checks if the page title contains the specified `$text`.  Inverse is `assertPageTitleNotContains`. 
`assertPageContains($text)` | Checks if the page contents anywhere contains `$text`. Inverse is `assertPageNotContains`. 
`assertHasCallout($type, $message)` | Checks if the most recent page requested contains a user message / callout with the type of `$type~ (success, error, or info) and contains the text in `$message`.  The inverse is `assertNotHasCallout` 
`assertHasFormError($type, $field_name)` | Checks if the page has a form validation error given by the `forms::validate_form()` method of the specified type (blank, email, alphanum) on the specified form field.  Inverse of this method is `assertNotHasFormError`. 
`assertHasHeading($hnum, $text)` | Checks if the page contains a <hX> tag with the specified text.  Inverse of this method is `assertNotHasHeading`.
`assertHasSubmit($value, $label)` | Checks if the last requested page contains a submit button with the specified value and label.  The inverse of this method is `assertNotHasSubmit`. 
`assertHasTable($table_alias)` | Checks if the page contains a HTML table component with the alias in Apex format (ie. PACKAGE:ALIAS) that is displayed via the `ae:function>` tag.  Inverse of this method is `assertNotHasTable`.
`assertHasTableField($table_alias, $col_num, $value)` | Checks if the specified HTML tab has a row containing the specified value in the specified column number.  Inverse of this method is `assertNotHasTableField`.
`assertHasDBRow($sql)` | Checks if one row exists in the mySQL database with the specified SQL query.  Inverse of this method is `assertNotHasDBRow`. 
`assertHasDBField($sql, $column, $value)` | Retrives one row from the database with the specified SQL statement, and checks if the specified column name matches the value.  The inverse of this method is `aasertNotHasDBField`. 
`assertHasFormField($name)` | Checkes if the last page requested contains a form field with the specified name.  The inverse of this method is `assertNotHasFormField` 
`assertStringContains($string, $text)` | Checks if the provided string contains the specified text.  Inverse of this method is `assertStringNotContains`. 
`assertFileContains($filename, $text)` | Checks if the specified file contains the specified text within its contents.  Inverse of this method is `assertFileNotContains`.





