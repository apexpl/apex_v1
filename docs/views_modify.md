
# Views - Execute PHP on Existing View

There will be times when you want additional PHP code executed on an already existing view that belongs to a
different package.  For example, you may want additional PHP code executed when viewing the home page of the
public web site.

This is actually quite simplistic in Apex, as an RPC call is made every time a view is displayed.  To execute
additional PHP code on an existing view, simply create a worker with the routing key `core.template`.  For
example, if developing a package named "casino", within terminal you would type something like:

`./apex create worker casino:parse_template core.template`

This will create a new file at */src/casino/worker/parse_template.php*.  Open this file up, and create a
`parse(EventMessageInterface $msg)` method within it, which will be executed every time a template is displayed within the
software.  The `$msg` variable passed is an event message object as explained in the [Event Dispatchers](event_dispatchers.md) page.  This 
method should return an associative array, and all key-value paris
within the returned array will be assigned to the view.


**Example**

~~~php
<?php
declare(strict_types = 1);

namespace apex\casino\worker;

use apex\app;
use apex\svc\db;
use apex\svc\redis;
use apex\app\interfaces\msg\EventMessageInterface as event;

class parse_template
{

/**
* Execute this method every time a template is parsed.
*/
public function parse(event $msg)
{

    // Get request details
    $request = $msg->get_request();

    // Make sure we're on the home page, otherwise return nothing
    if ($request['area'] != 'public' || $request['uri'] != 'index') { 
        return array();
    }

    // Do some stuff
    $bets = array();
    $bets[] = array('id' => 45, 'winner' => 'mike', 'amount' => 54.11),
        array('id' => 88, 'winner' => 'joan', 'amount' => 284.11)
    );
    $total_won = 853.33;
    $total_bets = 48;

    // Set response
    $response = array(
        'bets' => $bets,
        'total_won' => $total_won,
        'total_bets' => $total_bets
    );

    // Return
    return $response;

}

}
~~~

That's it~  As you can see, the function checks to ensure we're on the home page of the public site, and if
not, returns a blank array and stops executing.  Otherwise, it will grab some variables, and create a single
`$response` associative array which it returns. In turn, we can now use the `~total_won~` and `~total_bets~`
merge variables within the home page template, plus create an `<a:section name="bets"> ... </a:section>` block
on the home page which will loop through that `$bets` array we defined.










