
# Apex Training - Transaction Controller

Within our library class you will notice we added a transaction to the winner's account with the controller "lottery_win", so let's 
go ahead and create that controller now.  Within terminal,  type:

`./apex create controller transaction:transaction:lottery_win training`

This will create a new PHP file at */src/transaction/controller/transaction/lottery_win.php*, and will assign it to our "training" package, so 
it's included during publishing to a repository.  Open this file, and enter the following contents:

~~~php

<?php
declare(strict_types = 1);

namespace apex\transaction\controller\transaction;

use apex\app;
use apex\svc\db;

/**
 * Lottery winning transaction controller.
*/
class lottery_win   implements \apex\transaction\controller\transaction
{


    public $name = 'Lottery Winnings';


/**
 * Get amount 
 *
 * @param object $trans The transaction object.
 */
public function get_amount($tx)
{ 
    return (float) app::_config('training:daily_award');
}

/**
 * Gets the full name of the transaction to display within the table listing 
 * transactions. 
 *
 * @param array $row The row from the 'transaction' table.
 *
 * @return string The name to display in the web browser.
 */
public function get_name(array $row):string
{ 

    $name = 'Lottery Winnings';

    // Return
return $name;

}

/**
 * Approve transaction 
 *
 * @param object $trans The transaction object.
 */
public function approved($tx)
{ 
}


}

~~~

This class simply defines the default name of ththe transaction within the properties, returns the amount of the transaction via the 
`get_amount()` method, and returns the full name of the transaction within the `get_name()` method.  

That's it.  Everything is in place now, so if desired, visit the Maintenance->Cron Manager menu of the administration panel, and you can execute the 
"pick_winner" crontab job we previously created, which should now go through fine.


### Next

Next up, let's quickly develop the menus that we initially defined within our package.php file.  Let's start by 
developing the [Admin Settings View](admin_settings_view.md).


