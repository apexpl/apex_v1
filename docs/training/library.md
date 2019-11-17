
# Apex Training - Library

Let's create a quick library for our lottery package.  Libraries and simply blank PHP files which can 
coonnain any and all code you wish.  Within terminal, type:

`./apex create lib training:lottery`

This will create a new blank PHP file at */src/training/lottery.php*.  Open the 
file, and enter the following contents.

~~~php
<?php
declare(strict_types = 1);

namespace apex\training;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\transaction\tx;
use apex\transaction\processor;
use apex\app\msg\emailer;

/**
 * Class that handles all lottery functionality.
 */
class lottery
{

    /**
     * @Inject
     * @var emailer
     */
    private $emailer;


/**
 * Picke a winner.
 *
 * @return int The id# of the user who won.
 */
public function pick_winner()
{

    // Get random user
    if (!$userid = db::get_field("SELECT id FROM users WHERE status = 'active' ORDER BY RAND()")) { 
        return false;
    }

    // Get total users
    $total = db::get_field("SELECT count(*) FROM users");

    // Add to database
    db::insert('lotteries', array(
        'userid' => $userid, 
        'amount' => app::_config('training:daily_award'), 
        'total_entries' => $total) 
    );
    $lottery_id = db::insert_id();

    // Add transaction
    $tx = app::make(tx::class);
    $tx->set_user((int) $userid);
    $tx->set_amount((float) app::_config('training:daily_award'));
    $tx->set_controller('lottery_win');
    $tx->set_status('approved');
    $tx->set_reference_id((int) $lottery_id);

    // Create transaction
    $processor = app::make(processor::class);
    $processor->create_tx($tx);

    // Process e-mails
    $this->emailer->process_emails('lottery', (int) $userid, array('status' => 'complete'), array('lottery_id' => $lottery_id));

    // Return
    return(int) $userid;

}

}

~~~


This library simply contains one method that will pick the winner of the lottery, add a 
transaction to their account, and send out any necessary e-mail notifications.  If you will notice, this library also 
takes advantage of dependency injection via annotations with the e-mailer class.


### Next

Let's move on to creating that [Crontab Job](crontab.md), which will run every 24 hours and 
execute out one method in the above library.


