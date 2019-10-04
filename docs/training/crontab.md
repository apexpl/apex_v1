
# Apex Training - Crontab Job

Let's create our crontab job that will execute once every 24 hours, and randomly 
pick a winner using our `pick_inner()` method we created in the previous library.  Within tyerminal, type:

`php apex.php create cron training:pick_winner`

This will create a new file at */src/training/cron/pick_winner.php*.  Open the file, and enter the following contents.

~~~php
<?php
declare(strict_types = 1);

namespace apex\training\cron;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\training\lottery;

/** 
 * Class the handles the lottery package, and pickcing a 
 * winner every 24 hours.
 */
class pick_winner
{

    // Properties
    public $time_interval = 'D1';
    public $name = 'Lottery - Pick Winner';

/**
 * Picker lottery winner.
 */
public function process()
{

    // Get a random user
    $client = app::make(lottery::class);
    $userid = $client->pick_winner();

    // Return
    return $userid;

}

}

~~~


As you will notice within the properties of the above class, we set `$time_interval = 'D1';`, which represents one 
day and is the interval at which the crontab job will execute.  That's it, the above method will not execute every 24 hours.


### Next

Let's move on to [Creating a Notification Controller](notification_controller.md) to handle the e-mail messages that 
will be sent out.


