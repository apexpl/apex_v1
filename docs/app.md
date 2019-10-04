
# The `app` Class

The *apex\app* class is the main / central class for all Apex applications, and is used to facilitate every request.  It
handles the HTTP request and response, dependency injection container, services, stores all information
regarding the request such as inputted data via POST / GET methods, and more.

This class is a singleton, meaning only one instance is ever created per-request, allowing it to also be
accessed statically for better accessibility.  It is always loaded in virtually every PHP file by simply
loading the `apex\app` namespace such as:

~~~php
namespace apex\mypackage;

use apex\app;

... rest of the code ...
~~~


1. <a href="#input_arrays">Input Arrays (POST, GET, COOKIE, etc.)</a>
2. <a href="#config">Configuration Variables</a>
3. <a href="#apex_request">Apex Request</a>
4. <a href="#http_request">HTTP Request</a>
5. <a href="#http_response">HTTP Response</a>
6. <a href="#additional">Additional</a>
7. <a href="dependency_injection">Dependency Injection</a>



<a name="input_arrays"></a>
## Input Arrays

#### _post(), _get(), _cookie(), _server() Methods

For security purposes, never use the super globals such as `$_POST` and `$_GET`, and instead only use these
methods to retrieve input data, as these variables are properly sanitized.  For example:

~~~php
namespace apex;

use apex\app;

$category_id = app::_get('category_id') ?? 0;
$name = app::_post('full_name');
~~~


#### has_post(), has_get(), has_cookie(), has_server() Methods

These methods should always be used in place of PHP's built-in `isset()` function, and they simply return a
boolean whether or not the requested variable exists.  For example:

~~~php
namespace apex;

use apex\app;

if (!app::has_post('note')) {
    echo "No 'note' variable was defined!";
}
~~~


#### getall_post(), getall_get(), getall_cookie(), getall_server() Methods

These return an array consisting of all key-value pairs of the corresponding array.  For example:

~~~php
namespace apex;

use apex\app;

$post_vars = app::getall_post();
~~~


#### clear_post() / clear_get() Methods

This allows you to clear the POST and GET input arrays,  Useful for example, after a form has been submitted
and action completed successfully, you may want to clear the POST array so the form displayed on the template
again is not pre-filled in with the previously submitted data.



<a name="config"></a>
## Configuration Variables

All global configuration variables as defined by the installed packages can be retrived with the following
methods:


Method | Description 
------------- |------------- 
_config() | Get a single configuration variable (eg. *core:domain_name*, *transaction:base_currency*, etc.)
has_config() | Returns a boolean whether or not the configuration variable exists.
getall_config() | Returns an array consisting of all configuration variables.



<a name="apex_request"></a>
## Apex Request


There are various methods available to obtain and set information regarding the request that is specific to
Apex, and are explained in the below table.

Method | Description 
------------- |-------------
get_action() | Returns the value of any "submit" form field previously posted, blank otherwise.
get_uri() / set_uri($uri) | Gets or sets the URI that is being accessed, and is used to determine which .tpl template file from within the */views/* directory is displayed.
get_userid() / set_userid($userid) | Gets or sets the ID# of the authenticated user.  Generally, you should never have to set the userid as the authentication engine does that automatically, but useful to obtain the ID# of the user.
get_area() / set_area($area) | Gets or sets the current active area.  Generally, this will always be either "admin", "members" or "public", is automatically determined based on URI, and should never have to be set.
get_theme() / set_theme($theme) | Gets or sets the theme being displayed.  Generally, you will never need either of these, as they are used by the template engine only.
get_http_controller() | Gets the HTTP controller that will be handling the request, resides in the */src/core/controller/http_requests/* directory.


**Examples**

~~~php

namespace apex;

use apex\app;


// Submit button from previous form was "create"
if (app::get_action() == 'create') {

    // Display the /public/winner template instead if first 500 members.
    if (app::get_userid() <= 500) {
        app::set_uri('winner');
    }

}
~~~



<a name="http_request"></a>
## HTTP Request

The below methods allow you to retrive various information about the HTTP request itself. Please note, this
information is read-only and can not be set.

Method | Description 
------------- |-------------
get_ip() | IP address of the client connecting.
get_user_agent() | The user agent of the client connecting.
get_host() | The hostname being connected to (ie. your domain name)
get_port() | The port being connected to, generally either 80 or 443 (SSL).
get_protocol() | The HTTP protocol version being used, generally 1.1.
get_method() | The request method of the request (ie. GET, POST, DELETE, etc.)
get_content_type() | The content type passed by the client.
get_uri_segments() | An array of the URI split by the / character, with the first segment being left off in case it's an HTTP controller (eg. admin, members, repo, etc.)
get_request_body() | Get the full raw contents of the request body.  Useful if example, a chunk of JSON was POSTed to the software.
_header($name) | Array of all instances of the `$name` within the HTTP header sent by the client.
_header_line($name) | A single comma delimited line of all instances of the `$name` HTTP header passed by the client.



<a name="http_response"></a>
## HTTP Response

The below methods allow you to control the response that is given back to the client.

Method | Description 
------------- |-------------
set_res_http_status($code) | Set the HTTP status code of the response (eg. 404, 500, 403, etc..  Defaults to 200 OK.
set_res_content_type($type) | Set the content type of the HTTP response.  Defaults to "text/html".
set_res_header($name, $value) | Set a custom HTTP header within the response.
set_res_body($contents) | Set the actual contents of the response.
echo_response() | Outputs the response including HTTP status and headers to the browser.


<a name="additional"></a>
## Additional

There's various additional methods available that will be useful at times, and are explaind in the below
table.

Method | Description 
------------- |-------------
get_instance() | Returns the singular instance of the app class.  Never really needed.
update_config_var($var, $value) | Updates a configuration variable.
get_counter($name) | Increments the specified counter by 1, and returns the new integer.  Useful when you need incrementing numbers outside of the database.
get_timezone() | Returns the timezone of the current session.
get_language() | Returns the language of the current session.
get_currency() | Returns the base currency of the current session.



<a name="dependency_injection"></a>
## Dependency Injection

The app class also acts as the container for dependency injection.  This is explained in more detail elsewhere
in the documentation, but the methods available are explained below.


method | Description 
------------- |-------------
get($key) | Get the value of the key from the container.
has($key) | Returns a boolean as to whether or not the container contains the specified key.
set($key, $value) | Set a new value within the container.
make($class_name, array $params = []) | Creates and returns an instance of the specified class.  This instance is not saved within the container, and is only created with dependency injection, then returned.
call([$class_name, $method], array $params = []) | Call a specific method within a class while performing dependency injection directly on the method.  This does not save anything within the container, and only calls the method, but does allow for method / setter injection.




