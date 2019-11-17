
# Views - AJAX / Web Sockets

A full AJAX library is available alowing you to easily modify the DOM elements within the web browser without
writing any Javascript via both, AJAX and Web Sockets.  Below shows a quick example of creating an Ajax
object, and modifying some DOM elements with it:

~~~php
namespace apex;

use apex\app;
use apex\app\web\ajax;

$ajax = new ajax();
$ajax->remove_checked_table_rows('casino:bets');
$ajax->set_display('some_div', 'none');
$ajax->set_text('some_div', 'the new text');
~~~

For full information on all methods supported by the Ajax library, please visit the [apex\app\web\ajax PHP
Class](https://apex-platform.org/api/apex.app.web.ajax.html) page of the developer API reference.


### AJAX Functions

You can create [AJAX Components](../components/ajax.md) which can be executed by clicking on a link / button
within the web page, and modify the DOM elements as desired.  For example, within terminal you can type:

`./apex create ajax casino:place_bet`

A new file will be created at */src/casino/ajax/place_bet.php* where you can add any necessary PHP code
including calls to the AJax library to modify the DOM elements within the web page.  Within the .tpl file of
the web page you can execute the AJAX function by placing a link such as:

~~~
<a href="ajax_send('casino:place_bet');">Place Bet</a>
~~~

Upon clicking the link, the Ajax component we created will be executed, and the necessary DOM elements within
the web page will be modified as necessary.


### Web Sockets

Apex also contains a built-in web socket server, which is automatically connected to during every page load,
and allows DOM elements to be updated in real-time with no lag. This is useful for things such as chat bots,
social media messages, and other feeds.

Anywhere within the PHP code you can easily dispatch a message to the web socket server, which will be relayed
to the web browsers of the necessary recipients, and DOM elements modified accordingly.  You can dispatch a
message to a specific area (eg. administration panel, member's area), specific page of the site, or even
specific individuals who are logged in.

Below is an example of dispatching a web socket message:

~~~php
namespace apex;

use apex\app;
use apex\app\web\ajax;
use apex\app\msg\websocket;
use apex\app\msg\objects\websocket_message;

// Create Ajax object
$ajax = new ajax();
$ajax->alert("Hi there, here's a notice for you");
$ajax->set_text('some_div', "A message of some kind");

// Create new web socket message to dispatch
$msg = new websocket_message($ajax, 'members');

// Dispatch the message
$client = app::make(web_socket::class);
$client->dispatch($msg);
~~~

The above will send a web socket message to everyone currently logged into the member's area, provide an alert
message, and change the contents of the one div tag.


#### `websocket_message()` Object

As you can see from the above example, you create a
web_socket() object, which is then dispatched
to the necessary clients.  The constructor of this object allows the following parameters:

Variable | Type | Description 
------------- |------------- |------------- 
`$ajax` | Ajax Object | Object from the `apex\ajax` class, located at `/src/app/web/ajax.php`. 
`$recipients` | array | An array of specific individuals to send the message to, formatted in either `user:XX` or `admin:XX`, where `XX` is the ID# of the user or administrator. For example, `user:841` will send the message to user ID# 841, assuming they are logged in and active on the site. 
`area` | string | If defined, will only send messages to users currently viewing the specified area (admin, members, or public). 
`uri` | string | If defined, will only send the message to users with the specific page opened, relative to the area (eg. `area` = admin, and `$route` = users/create will only sent to people viewing the Users->Create New User menu of the administration panel)


### alerts::dispatch_notification(string $recipient, string $message, string $url)

**Description:**Both the administration panel and member's area contain a drop-down list available via an icon
in the top right corner of the screen.  This function allows you to add an additional alert into that
drop-down list in real-time.

**Parameters**

Variable | Type | Description 
------------- |------------- |------------- 
`$recipient` | string | The user to add the alert to, formatted in either `user:XX` or `admin:XX` where `XX` is the ID# of the user / administrator. 
`message` | string | The message to add into the dropdown list item. 
`$url` | string | The URL (relative or absolute) to link to dropdown list item to.

**Example**

~~~php
namespace apex;

use apex\message;

use apex\app;
use apex\app\msg\alerts;

// Add alert to user IDID# 53
$clients = app::make(alerts::class);
$client->dispatch_notification('user:53', "An important alert just came in for you...", "members/some_menu?action=592831");
~~~


### alerts::dispatch_message(string $recipient, string $from, string $message, string $url)

**Description:** Similar to adding a new dropdown alert as explained above, but adds the dropdown item to a
different list that is available within the administration panel and member's area just to the right of the
alerts dropdown list.  This is meant for actual messages, e-mails, private messages, etc.

**Parameters**

Variable | Type | Description 
------------- |------------- |------------- 
`$recipient` | string | The user to add the message to, formatted in either `user:XX` or `admin:XX` where `XX` is the ID# of the user / administrator. 
`$from` | string | Who the message is from, can be any string / name you wish. 
`message` | string | The message to add into the dropdown list item. 
`$url` | string | The URL (relative or absolute) to link to dropdown list item to.

**Example**

~~~php
namespace apex;

use apex\app;
use apex\app\msg\alerts;


// Add message to administrator with ID# 1
$client = app::make(alerts::class);
$client->dispatch_message('admin:1', "John Smith (jsmith)", "Could use some help with getting this to work...", "admin/support/view_ticket?id=4239");
~~~



