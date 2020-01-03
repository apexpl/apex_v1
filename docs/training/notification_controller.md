
# Apex Training -- Notification Controller

As you will notice, within our library class, we used dependency injection via annotations to injection the emailer class, 
then called the `$emailer->processor_emails()` method within our `pick_winner()` method.  Let's create the notifications 
controller to send out the e-mails.  Within terminal, type:

`./apex create controller core:notifications:lottery training`

This will create a new PHP file at */src/core/controller/notifications/lottery.php*, and will assign it to the "training" package, so 
upon publishing the package this PHP file will be included.  Open this file, and enter the following contents:

~~~php

<?php
declare(strict_types = 1);

namespace apex\core\controller\notifications;

use apex\app;
use apex\libc\db;
use apex\users\user;

/**
 * E-amil notifications controller for the lottery package.
 */
class lottery
{

    // Properties
    public $display_name = 'Lotteries';

    // Set fields
    public $fields = array(
        'status' => array('field' => 'select', 'label' => 'Status', 'data_source' => 'hash:training:status')
    );

    // Senders
    public $senders = array(
    'admin' => 'Administrator',
    'user' => 'User'
    );

    // Recipients
    public $recipients = array(
    'user' => 'User',
    'admin' => 'Administrator'
    );

/**
 * Get available merge fields.  Used when creating notification via admin 
 * panel. 
 */
public function get_merge_fields():array
{ 

    // Set fields
    $fields = array();
    $fields['Lottery'] = array(
        'lottery-id' => 'ID', 
        'lottery-amount' => 'Amount Won', 
        'lottery-date_added' => 'Date Added'
    );

    // Return
    return $fields;

}

/**
 * Get merge variables 
 *
 * @param int $userid The ID# of the user e-mails are being processed against.
 * @param array $data Any extra data needed.
 */
public function get_merge_vars(int $userid, array $data):array
{ 

    // Get lottery row
    if (!$row = db::get_idrow('lotteries', $data['lottery_id'])) { 
        return array();
    }

    // Set variables
    $vars = array(
        'lottery-id' => $row['id'], 
        'lottery-amount' => fmoney((float) $row['amount']);
        'lottery-date_added' => fdate($row['date_added'])
    );

    // Return
    return $vars;

}

}

~~~

Once the file is saved, you can visit the Settings->Notifications menu of the administration panel, and you will be able to create e-mail notifications 
for our lottery package.  When creating an e-mail notification, on the second page you will see a select list of available merge fields, which will include 
the fields defined within the `get_merge_fields()` method in the above controller class.

Within our library class, you will notice on line 63 we have:

~~~php
$this->emailer->process_emails('lottery', (int) $userid, array('status' => 'complete'), array('lottery_id' => $lottery_id));
~~~

This executes the emailer::process_emails() method, which will check any e-mail notifications created by the administrator, and 
send out the necessary e-mail messages.  Upon sending an e-mail, it will execute the above `get_merge_vrs()` method to retrieve the details on the 
specific lottery to personalize the e-mail message with.


### Next

Next up, within our library class we also added a transaction to the winner's account.  Let's go ahead and 
create the [Transaction Controller](transaction_controller.md) for it next.



