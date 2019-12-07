
# Testing E-Mail / Messages

Through the */bootstrap/test.php* configuration file, the emailer.php class is swapped out with the 
*/src/app/tests/test_emailer.php* class.  This class places all outgoing e-mail messages in a queue, instead of actually sending them, allowing 
you to search and read the queue using the below methods.

Please view the <a href="https://apex-platform.org/api/classes/apex.app.msg.objects.email_message.html">email_message</a> object class for details on how to 
retrive the subject, message contents, and other information about each e-mail message within the queue.


### emailer::search_queue($to_email, $from_email, $subject, $message)

**Description:** Will search the queue of all e-mail messages that were sent during execution of the unit tests, and return 
an array of <a href="https://apex-platform.org/api/classes/apex.app.msg.objects.email_message.html" target="_blank">email message</a. objects that match the 
provided search criteria.  All four search parameters are optional, and only checkd if defined.


**Example**

~~~php
<?php
declare(strict_types = 1);

namespaces tests\mypackage;

use apex\app;
use apex\svc\db;
use apex\app\msg\emailer;
use apex\app\tests\test;


class test_someclass {

    function test_something()

        // Clear e-mail queue
        $emailer = app::get(emailer::class);
        $emailer->clear_queue();

        // perform some action that sends e-mails //

        // Search e-mail queue for any messages with "verify" within the subject
        $messages = $emailer->search_queue('', '', 'Verify');
        $this->assertCount(1, $messages, "Unable to find e-mail message with Verify in the subject");

        // Get body of first e-mail message found
        $body = $messages[0]->get_message();

    }
}
~~~


### emailer::get_queue()

**Description:** Returns an array of email_message objects of all e-mail messages currently within the queue.


### emailer::clear_queue()

**Description:** Clears the existing e-mail queue, and useful to ensure you get the latest e-mail message instead of an e-mail 
that was sent during a previous test.


### emailer::clear_queue()

**Description:** Simply clears the current e-mail queue, and recommended at the begining of a test method before 
sending / testing outgoing e-mails.


### SMS / Web Socket Messages

All SMS and Web Socket messages that are sent while unit tests are being performed are automatically stored within two redis lists named, 
`test:sms_queue` and `test:websocket_queue`.  Both lists simply consist of JSON encoded strings that are added to for 
every message that is sent.  Within your unit tests, simply check these lits for proper assertions.  It's always a 
good idea to delete the necessary list at the beginning of your test method.

**Example**

~~~php
function test_sms_message()
{

    // Clear list
    redis::del('test:sms_queue');

    // Send SMS message
    $sms = new sms_message('16045551234', 'A test message', 'My Name');
    $client = app::make(sms::class);
    $client->dispatch($sms);

    // Check for message
    $messages = redis::lrange('test:sms_queue', 0, -1);
    $this->assertCount(1, $messages, "There should only be one message in SMS queue, but there isn't!");

    // Check message
    $chk_message = json_decode($messages[0], true);
    $this->assertEquals('16045551234', $chk_message['phone']);

}
~~~


