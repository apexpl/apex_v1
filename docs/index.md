
# Apex Platform

Welcome to Apex, a powerful PHP software platform to efficiently develop, deploy, and maintain professional
online operations.  You  will be amazed at the simplicity for the quality.  Please take a few moments to
browse the documentation below.

Donations gratefully accepted.  Bitcoin address:  3JnYmUHhz1CKz9vgxX55qmBnzVRPirA21D or PayPal at payments@apex-platform.org.


#### Other Documentation

1. [Developer Training Guide](training/index.md)
2. [Developer API Reference (php-documentor)](https://apex-platform.org/api/)
3. [User Manual](core/index.md)

# Table of Contents

1. [What is Apex?](about.md)
2. [Installation](install.md)
3. [Getting Started](getting_started.md)
    1. [The `app` Class](app.md)
    2. [Request Routing (http / cli)](routing.md)
    3. [Services Container](services.md)
    4. [Dependency Injection](di.md)
    5. [Global Functions](global_functions.md)
4. [Packages and Components](packages.md)
5. [Repositories](repos.md)
6. [`apex` CLI Commands](cli.md)
7. [Views](views.md)
8. [Themes](themes.md)
9. [Event Dispatchers](event_dispatchers.md)
    1. [Listeners](listeners.md)
10. [Communication (e-mail / SMS)](communicate.md)
11. [Error Handling, Logging / Debugging](logging.md)
12. [Unit Tests](tests.md)


### Services Container

The services container provides easy access to various classes that are used to aide in development.  These
include classes such as database interface, redis storage engine, template parser, event dispatcher, debugger,
and more.

Service | Description 
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




