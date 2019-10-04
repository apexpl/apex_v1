
# redis:: Service -- redis Storage Engine

Apex comes with built-in support for redis, which is a popular in-memory storage engine allowing for extremely
quick retrieval and storage of data.  The php-redis extension is used, which provides a very easy and
efficient API to communicate with redis.

This service provides full access to the php-redis class, for example:

~~~php
namespace apex;

use apex\app;
use apex\svc\redis;

redis::hset('some_hash', 'var_name', 'value');
$value = redis::hget('some_hash', 'var_name');
~~~

For more information on redis, the data types and functions available, and php-redis, please visit the below
links.

> [redis Data Types](https://redis.io/topics/data-types) > [php-redis](https://redislabs.com/lp/php-redis/)


