
# Library Container

There are various small PHP classes available within the */src/libc/* directory, which allow various
important components / libraries of Apex to be statically accessed.  For example:

~~~php
namespace apex;

use apex\app;
use apex\libc\db;
use apex\libc\redis;
use apex\libc\debug;

function some_method()
{

    // Debug
    debug::add(3, tr("My test debug line"));

    // Redis set
    redis::set('mykey', 'some_value');

    // Database
    $names = db::get_column("SELECT name FROM products ORDER BY name");
    foreach ($names as $name) {
        echo "Product: $name\n";
    }

}
~~~

In the above example it appears we are accessing the methods statically, but in reality we are not.  All three
classes were created by and retrieved from the dependency injection container, using the classes defined within the bootstrap
configuration file (see below).  The small /libc/ classes simply relay the calls to the proper PHP classes
non-statically, providing for greater simplicity and accessibility, while still utilizing the power and
flexibility of dependancy injection.


## Libraries Available

The below table lists all libraries available within the main Apex platform.  Please note, installed packages
may offer additional libraries other than those listed below.

Library | Description 
------------- |------------- 
[db](database.md) | The back-end database (mySQL,PostgreSQL, etc.). 
[redis](redis.md) | The redis connection utilizing the popular php-redis extension.
[msg](event_dispatchers.md) | Event dispatcher to send one-way direct or two-way RPC calls to listeners / workers. 
[view](https://apex-platform.org/api/classes/apex.app.web.view.html) | Template engine that parses and displays .tpl files. 
[debug](https://apex-platform.org/api/classes/apex.app.sys.debug.html) | Debugger which also doubles as the log handler.
[log](https://apex-platform.org/api/classes/apex.app.sys.log.html) | The log handler, only useful if you want to add log entries outside of the debugger.
[cache](https://apex-platform.org/api/classes/apex.app.io.cache.html) | Simply caching handler.
[storage](https://apex-platform.org/api/classes/apex.app.io.storage.html) | File handline and management on remote networks, services such as AWS, etc.
[auth](https://apex-platform.org/api/classes/apex.app.sys.auth.html) | Authentication library, checks authenticated sessions, and logs in users.
[components](https://apex-platform.org/api/classes/apex.app.sys.components.html) | Provides easy access to check and load components, and call methods within them via dependancy injection.
[date](https://apex-platform.org/api/classes/apex.app.utils.date.html) | Various date functions, such as adding / subtracting a time interval.
[encrypt](https://apex-platform.org/api/classes/apex.app.sys.encrypt.html) | Allows various forms of encryption / decryption, such as basic, user segregated, and PGP.
[forms](https://apex-platform.org/api/classes/apex.app.utils.forms.html) | Provides various methods to facilitate handling HTML forms, including server-side form validation, retrieving values of a date / date interval field, and more. 
[geoip](https://apex-platform.org/api/classes/apex.app.utils.geoip.html) | Allows you to easily GeoIP an IP address.
[hashes](https://apex-platform.org/api/classes/apex.app.utils.hashes.html) | Various methods to parse and load the hashes which are defined within package configuration. Mainly used for select lists.
[images](https://apex-platform.org/api/classes/apex.app.utils.images.html) | Easily manage databases of images -- upload, add, search, generate thumbnails, etc.
[io](https://apex-platform.org/api/classes/apex.app.io.io.html) | Various methods allowing for easy manipulation and parsing of files and directories including zip archives.


## Bootstrap Configuration Files

The */bootstrap/* directory contains one PHP configuration file for each of the different request types (http,
cli, test), and defines the exact PHP classes to load for each service.  For example, if you wanted to switch
to the monolog log handler, or a different database driver, you would do so within these configuration files.
For another example, the emailer service is changed in the test.php script to a different PHP class that
instead of sending the e-mail messages via SMTP, it returns them to the unit test for assertion testing.

Please take a look at the existing configuration files to see how they are formatted, but they are quite
straight forward.  They simply return one associative array, the keys being the interface / class name (key
within the container), and the value being an array defining the PHP class to load.




