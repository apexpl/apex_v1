
# Event Dispatchers

Apex has built-in support for RabbitMQ and horizontal scaling to easily handle operations with large volume.
If you are unfamiliar with RabbitMQ or horizontal scaling, simply put, it's a messaging service that resides
in between the front-end web servers and back-end application servers.  When the front-end servers need to
perform an action that is potentially resource intensive (eg. user registration), it will dispatch a message
to RabbitMQ, which then evenly distributes the messages to the back-end application servers.

This way, if the online operation ever becomes burdened due to high volume, you can simply add more back-end
application servers which will immediately begin helping handle the load since all resource intensive
operations are distributed evenly amongst them.  If desired, you may learn more about RabbitMQ by visiting
the [RabbitMQ PHP Tutorial](https://www.rabbitmq.com/tutorials/tutorial-one-php.html) site.


### Dispatching Messages

All processes that may become burdensome with high volume should be performed via event dispatchers /
listeners.  Dispatching messages is very simple, and for example:

~~~
namespace apex;

use apex\app;
use apex\svc\msg;
use apex\app\msg\objects\event_message;

// Define some message, can be anything -- variable, array, object, etc.
$data = 'anything, array / object, whatever';

// Define the message
$event = new event_message('casino.games.place_bet', $msg);

// Dispatch the message
$response = msg::dispatch($event)->get_response('casino');
~~~

The above will dispatch a message to the "casino.games" routing key, execute the "games" method of any
matching listeners, and return the response given by the "casino" package. For full details on how these
messages are received and processed, please visit the [Event Listeners](event_listeners.md) page of the
documentation.


### Event Messages

You can only dispatch <a href="https://apex-platform.org/api/classes/apex.app.msg.objects.event_message.html"
target="_blank">event messages</a> that implement the `EventMessageInterface`.  This helps ensure the messages
get relayed to the correct listeners, and allow you to pass any data you wish.  When defining an event
message, you need both, the routing key and data you would like to send.

The routing key is a string comprised of three segments, separated by periods.  First two segments will
be pre-defined and specify which package and listener alias to route the message to, and the third segment is
the method to execute.  For example, the routing key "users.profile.created" will execute the "created" method
of any listeners assigned to "users.profile".  Refer to the documentation of the packages you are integrating
with for which routing keys are available.

The data sent can be anything you wish -- variable, array, object, etc.  For another example of creating an
event message:

~~~php
namespace apex;

use apex\app;
use apex\svc\msg;
use apex\app\msg\objects\event_message;

$data =
    'id' => 54,
    'name' => 'John Doe',
    'email' => 'john@doe.com'
);
$msg = new event_message('mypackage.lalias.process', $data);

// Dispatch the message
$response = msg::dispatch($msg);
~~~

Simple as that.  This will pass the data to the "process" method of any PHP listener classes that are assigned
to the "mypackage.lalias" routing key.  The response is an associative array, the keys being the alias of each
package that a listener was found for, and the value being the response given by that package.  This is due to
the fact that one message may be dispatched to PHP listener classes in multiple packages.

### Listeners

Now that you know how to dispatch messages, learn how to create listeners and process the messages received by
reading the [Event Listeners](event_listeners.md) page of the documentation.






