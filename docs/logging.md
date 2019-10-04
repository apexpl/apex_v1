
# Error Handling, Logging / Debugging

Apex allows for very straight forward and simple error handling, logging, and debugging functionality.  The
eight PSR compliant log levels are fully supported, plus both debugging and logging functionality has been
semi-combined for better efficiency.  There is also a built-in debugger that automatically displays when in
development mode.


## Error Handling

All errors within Apex are given off by throwing exceptions, mainly the `ApexException` exception.  There are
multiple exception classes available, which you can view within the /src/app/exceptions/ directory of the
software.


#### `throw new ApexException(string $level, string $message, [...$vars])`

**Description:** This allows you to throw a general error anywhere within Apex, which will be logged and
rendered as necessary.  If viewing from the web browser, the appropriate error .tpl template will be
displayed, if via JSON (ie. the response content-type is set to "text/json") it will return a JSON formatted
error, and if executing via CLI / terminal, it will simply render a plain text error within the terminal.

**Parameters**

Variable | Type | Description 
------------- |------------- |------------- 
`$level` | string | One of the eight supported PSR3 log levels, and if ensure, simply use "error". Can be: `debug, info, warning, notice, error, alert, critical, emergency`
`$message` | string | The error message, which is processed through the global tr() function to support placeholders (eg. {1}, {2}, etc.)
`vars` | array | Optional array of variables, which placeholders within the message are replaced with.

**Example**

~~~php
namespace apex;

use apex\app\exceptions\ApexException;

$x = 5;
if ($x < 9)
    throw new APexException('error', "Uh oh, x is not supposed to be less than 9, but is {1}", $x);
}
~~~


## Logging / Debugging with debug::add()

Both logging and debugging can be handled simultaneously with the single
debug::add() method.  This method fully supports the eight PSR3 log levels, and will add entries to both the debug session and appropriate log file
at the same time.

**Parameters**

Variable | Type | Description 
------------- |------------- |------------- 
`$level` | int | Integer between 1 - 5, defining the minimum debug level to add the entry to the debug session. 
`$message` | string | The message of the log / debug entry. 
`$log_level` | string | Optional log level of entry, and if defined, will also add the entry to the appropriate log file.  Supports the PSR compliant log levels, and can be any of: info, warning, notice, error, alert, critical, emergency.

**Example**

~~~php
namespace apex;

use apex\app;
use apex\svc\debug;

// Do something //
$amount = 15;
debug::add(3, tr("Did something really cool, resulting amount is {1}", $amount), 'info');
~~~

The above line will add the entry to the debug session assuming the debug level is set to 3 or higher and
development mode is on, plus will add the entry to the INFO log file.


#### Log Files -- /storage/logs/

All logs are stored within the */storage/logs/* directory, which will contain the following files:

File | Description 
------------- |------------- 
access.log | One line for every request to the system, Similar  to a standard Nginx / Apache access log. 
messages.log | The main log file which contains entries of all log levels combined.
system.log | Messages given off by the PHP parser itself, and not within the Apex software (eg. undefined index, etc.) 
sql_error.log | Contains all SQL statements that resulted in an error from mySQL 
LEVEL.log | One file for each of the eight supported PSR3 log levels (eg. info.log, alert.log, etc.)


#### `log::LEVEL(string $message, [array $context = array()])`

These methods are available anywhere within Apex, and allow you to easily add a log entry under any of the
eight supported PSR3 defined log levels.  Simply replace the method name `LEVEL` with one of the eight levels
(`debug, info, warning, notice, error, alert, critical, emergency`), and the appropriate log entry will be
added.

**Example**

~~~php
namespace apex;

use apex\app;
use apex\svc\log;

log::info("Here is some info about what's happening");

log::error("Houston, we have a problem!");

log::warning("Not sure, but might want to take a look at this.  Better log it just in case");

~~~


#### Placeholders

Full support for placeholders is also available as defined by PSR3.  Placeholders are simply numbers surrounded by left and right braces, such as `{1}` and `{2}`', and are easy to use.  For example:

~~~php
$name = 'John Doe';
$amount = 53.11;

log::warning("Payment from {1} was {2} lower than expected.", array($name, $amount));
~~~

Above would add a log message that says *"Payment from John Doe was 53.11 lower than expected."*.


#### Channels

Every log entry contains the channel name, which by default is "apex".  Support for multiple channels is
available though, which allows you to easily filter the log file for only entries that are specific to your
application.  You simply need to create a new instance of the "log" class and pass the channel name into it,
such as:

~~~php
$logger = new log('my_package');

$logger->error("Something bad happend!");
~~~

That's it.  Now every entry added via `$logger` will include the channel name "my_package"


## Debugger

Assuming the system is set to development mode, the debugger is always on,  and upon getting an error within
the web browser a tab control will be displayed showing full details on the request, allowing you to easily
diagnose the issue.

At times you will also want to save a debug session for later inspection.  To do this, within terminal type:
`php apex.php debug 1`.  The debug session of the next request will be saved, and can be viewed any time via
the *Devel Kit-&gt;Debugger* menu of the administration panel.  The system only retains one debug session at a
time, so upon saving another session the prior one will be overwritten.


#### Server Mode (development / production)

You can easily switch the system between development and production modes, which modifies how errors are
handled.  When in production mode no details on errors are displayed, and either only the error message itself
is displayed, or for more internal errors (eg. SQL error) a generic error template is displayed that gives no
error message at all.  However, when in development mode, full details on errors are displayed including a tab
control providing all debugger information on the request.

You can easily switch between development / production modes via the Settings->General menu of the
administration panel, or via the terminal by running:

`php apex.php mode [devel|prod] [DEBUG_LEVEL]`.

The DEBUG_LEVEL can be 0 - 5 and defines how extensive of logging you would like to view within the debugger.
Generally, a debug level of 3 should be sufficient to pinpoint any errors / bugs.




