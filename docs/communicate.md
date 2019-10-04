
# Communication (e-mail / SMS)

Sending both, e-mail and SMS messages is quite straight forward within Apex, and everything you need is
explained below.


1. <a href="#email_servers">E-Mail Servers</a>
2. <a href="#send_email">Send Individual E-Mail</a>
3. <a href="#define_notifications">Define Notifications</a>
4. <a href="#process_emails">Process E-Mail Notifications</a>
5. <a href="#create_controller">Create Notification Controller</a>
6. <a href="#default_notifications">Define Default Notifications</a>



<a name="email_servers"></a>
### E-Mail Servers

Within the *Settings->General* menu of the administration panel you can define multiple SMTP servers.  Apex
will evenly distribute all outgoing e-mails amongst the SMTP servers listed in this menu.

<a name="send_email"></a>
### Send Individual E-Mail

Although not generally required, sending an individual e-mail manually is quite simple with Apex by using the
emailer::send() method.

**Example:**

~~~php
namespace apex;

use apex\app;
use apex\app\msg\emailer;

$emailer = app::make(emailer::class);
$emailer->send("customer@domain.com", "Customer Name", "support@mydomain.com", "My Company", "Your Invoice", "Please find below your invoice for this month....");
~~~

For full details on this method and its parameters, please visit the
emailer::send() API page.

<a name="define_notifications"></a>
### Define Notifications

Apex uses a conditional trigger based system for e-mail notifications, and all e-mail messages can be defined
through  the *Settings->Notifications* menu of the administration panel.  Through this menu you can select the
notification type, which will display the conditions that need to be  met for the e-mail to go out (eg. user
created, transaction status changed to approved, etc.).  On the next page you can define the actual contents
of the e-mail message, and on this page it also lists all available merge variables that are supported by the
notification type.

See below for how to develop your own notification types / controllers, and how to add default e-mail
notifications into package installation.


<a name="process_emails"></a>
### Processing E-Mails emailer::process_emails()

You can trigger any necessary e-mails to be sent via the
emailer::process_emails() function.  This function will go
through all notifications of a specific controller, and check the conditional requirements against the
condition passed, and if they match will automatically send the necessary e-mail messages.

**Example:**

~~~php
namespace apex;

use apex\app;
use apex\app\msg\emailer;

// Set variables
$userid = 54;
$transaction_id = 1593;
$status = 'declined';

// Process notifications
$emailer = app::make(emailer::class);
$emailer->process_emails('transaction', $userid, array('status' => $status), array('transaction_id' => $transaction_id));
~~~

This will go through all notifications created with the controller alias "transaction", check the conditional
arguments they were created with, and for any that match a "status" of "declined", will send the e-mail
message.  Again, see below for creating your own notification type to get a better understanding of how this
works.  Also please see the
emailer::process_emails() method within the API documentation
for details on parameters.


<a name="create_controller"></a>
### Create Notification Controller

There will be times where you need to create your own notification controller, allowing the administrator to
define their own set of e-mail notifications, and have them automatically sent when certain actions occur
within the software.  For example, you may be creating a lottery package, and want various notifications sent
when people enter or win the lottery.  To do this, within terminal create the controller by typing:

`php apex.php create controller core:notifications:lottery lottery`

This will create a notification controller with the alias "lottery", and include it within the package
"lottery", and a new PHP file will be located at */src/core/controller/notifications/lottery.php*.  Below
explains all methods available within this PHP class.

#### Properties

Variable | Type | Required | Description 
------------- |------------- |------------- |-------------
`$display_name` | string | Yes | The display name of the notification type which is displayed in the web browser / administration panel. 
`$fields` | array | Yes | An array of form fields that define the available conditional arguments the administrator can select from when defining e-mail notifications.  This is the same array as standard "form" components within Apex, and please visit the [HTML Forms](components/form.md) page for further information on how to format this array. 
`$senders` | array | Yes | Array listing the available senders that the administrator can choose from when defining notifications for this notification type.  See below for more info. 
`$recipients` | array | Yes | Array listing the available recipients that the administrator can choose from when defining notifications for this notification type.  See below for more info.

#### `$senders / $recipients` arrays

When defining an e-mail notification via the *Settings->Notifications* menu, you will notice there are select
lists allowing the administrator to choose who the sender and recipient of the e-mail notification are. These
two arrays define what options are available within those select lits for this notification type.  The keys of
the array are the database values, while the values of the arrays are what is displayed within the web
browser.  The keys are generally always "admin" and "user", where when "admin" appears as a key it will be
replaced with a list of all administrators currently in the database. However, this can also be used for other
recipients / senders, such as the support ticketing system includes the key "support_tech", which checks the
tech assigned to the given support ticket and notifies them via e-mail a response has been made.


#### `array = get_sender(string $sender) / array = get_recipient($recipient)`

These two methods are optional within the class, and are only needed if the `$senders / $recipients` property
arrays include anything other than "admin" and "user" as keys.  When determining the sender / recipient e-mail
address and name, the software will first generally check if it's "admin" or "user", and if not, will call
this method.  This method needs to return the e-mail address and name of the sender / recipieint as an array.


#### `array get_merge_fields()`

This returns an array of associative arrays providing all the merge variables that are available to this
notification type.  These are the merge variables that can be placed within the e-mail message, and be
replaced with personalized information.  Each notification type is different, such as for example, transaction
based notification have transaction related merge variables available to them.  For examples of how this array
looks, please look at the existing PHP classes within the */src/core/controller/notifications/* directory.

This function is only executed while the administrator is defining e-mail notifications via the
*Settings->Notifications* menu.


#### `array = get_merge_vars(int $userid, array $data)`

This function is executed every time an e-mail of this notification type is sent, and gathers the values of
the merge variables to personalize the e-mail message with.  The keys of the array returned must be the same
as the keys of the array given in the `get_merge_fields()` function explained above.  Apex will go through all
key-value pairs in the array, and place `~key~` with the respective value within the subject and contents of
the e-mail message.


<a name="default_notifications"></a>
### Define Default Notifications

You may also define default e-mail notifications to be created upon package installation, making setup easier
for the administrator, instead of forcing them to define their own e-mail messages.  For example, upon
installing the User Management package, there are default e-mail notifications created that are sent to the
administrator and user upon registration, password reset request, and so on.

You can define default e-mail notifications by modifying the */etc/PACKAGE/package.php* file of the package.
Simply add a `$this->notifications` array as necessary.  For full information on this 
array including examples, please visit the [Package Configuration - notifications array](packages_config.md#notifications) page of the documentation.

