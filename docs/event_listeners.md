
# Event Listeners

Event listeners, also currently known as the [worker Component](components/worker.md) are PHP classes that
receive and process incoming messages dispatched from the front-end servers.  These listeners will reside on
the back-end application servers, and handle the resource intensive operations of the system.

Again, routing keys are a string comprised of three elements, separated by a comma.  The first element is the
package alias, the second any alias you wish for the listener, and the third is the method name within the PHP
listener class.  You can create a new listener by creating a new [worker Component](components/worker.md), and
for example within terminal type:

``./apex create worker casino.games casino.games`

This will create a blank PHP class at */src/casino/worker/games.php* which you can fill with any methods you
desire.  For example, maybe you add a `place_bets()` method, such as:

~~~php
<?php
declare(strict_types = 1);

namespace apex\casino\worker;

use apex\app;
use apex\svc\db;
use apex\app\msg\objects\event_message as event;


class games {

    function place_bet(event $msg) {

        // Get data sent
        $data = $msg->get_params();

        // Get request info (URI, area, userid, etc.)
        $request_info = $msg->get_request();
        print_r($request);

        // Get file / line / method it was sent from
        $caller = $msg->get_caller();
        print_r($caller);

        // Do something to place the bet //

        // Return response, anything we wish -- variable, array, object, etc.
        $bet_id = 'unique_bet_id';
        return $bet_id;
    }

}
~~~

Now that we have our listener in place, we can start dispatching messages to it from the views / front-end
servers.  For example:

~~~php
namespace apex;

use apex\app;
use apex\svc\msg;
use apex\app\msg\objects\event_message;

// Place new bet
if (app::get_action() == 'bet') {

    // Gather bet details
    $bet_data = array( ... );

    // Dispatch the message
    $msg = new event_message('casino.games.place_bet', $bet_data);
    $unique_bet_id = msg::dispatch($msg)->get_response('casino');

        // Echo
        echo "Bet placed, ID# $unique_bet_id";

    }

}
    }

}
~~~


### Example -- Execute PHP upon user Registration

Again, you will need to consult the documentation of the packages you are developing with to know which
routing keys they support.  For another example, say we want to execute PHP code every time a new user is
registered, which we can do via the "users.profile.created" routing key.  First, create a new worker /
listener, and in terminal type:

`./apex create worker casino:users users.profile~

This will create a blank PHP listener class at */src/casino/worker/users.php*, and will begin receiving all
messages dispatched to the "users.profile.*" routing key.  Within the PHP class, add a `created()` method such
as:

~~~php
<?php
declare(strict_types = 1);

namespace apex\casino\worker;

use apex\app;
use apex\svc\db;
use apex\app\msg\objects\event_message as event;


class users {

    function created (event $msg) {

        // Get userid passed
        $userid = $msg->get_params();

        // Do something with new user

        // Return
        return true;
    }
}
~~~

That's it.  Now every time a new user is registered, that above method will be executed.



