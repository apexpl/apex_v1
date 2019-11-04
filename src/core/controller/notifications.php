<?php
declare(strict_types = 1);

namespace apex\core\controller;

use apex\app;
use apex\svc\db;
use apex\users\user;
use apex\core\admin;

/**
 * Abstract nofications controller, used as a template 
 * for the actual notification controller.
 */
class notifications
{

    // Default code
    public $default_code = 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XGNvcmVcY29udHJvbGxlclxub3RpZmljYXRpb25zOwoKdXNlIGFwZXhcYXBwOwp1c2UgYXBleFxzdmNcZGI7CnVzZSBhcGV4XGNvcmVcY29udHJvbGxlclxub3RpZmljYXRpb25zOwp1c2UgYXBleFx1c2Vyc1x1c2VyOwp1c2UgYXBleFxjb3JlXGFkbWluOwoKLyoqCiAqIEFic3RyYWN0IG5vZmljYXRpb25zIGNvbnRyb2xsZXIsIHVzZWQgYXMgYSB0ZW1wbGF0ZSAKICogZm9yIHRoZSBhY3R1YWwgbm90aWZpY2F0aW9uIGNvbnRyb2xsZXIuCiAqLwpjbGFzcyB+YWxpYXN+IGV4dGVuZHMgbm90aWZpY2F0aW9ucwp7CgogICAgLy8gUHJvcGVydGllcwogICAgcHVibGljICRkaXNwbGF5X25hbWUgPSAnQ29udHJvbGxlciBOYW1lJzsKCiAgICAvLyBTZXQgZmllbGRzCiAgICBwdWJsaWMgJGZpZWxkcyA9IGFycmF5KAoKICAgICk7CgogICAgLy8gU2VuZGVycwogICAgcHVibGljICRzZW5kZXJzID0gYXJyYXkoCiAgICAgICAgJ2FkbWluJyA9PiAnQWRtaW5pc3RyYXRvcicsCiAgICAgICAgJ3VzZXInID0+ICdVc2VyJwogICAgKTsKCiAgICAvLyBSZWNpcGllbnRzCiAgICBwdWJsaWMgJHJlY2lwaWVudHMgPSBhcnJheSgKICAgICAgICAndXNlcicgPT4gJ1VzZXInLAogICAgICAgICdhZG1pbicgPT4gJ0FkbWluaXN0cmF0b3InCiAgICApOwoKLyoqCiAqIEdldCBhdmFpbGFibGUgbWVyZ2UgZmllbGRzLiAgVXNlZCB3aGVuIGNyZWF0aW5nIG5vdGlmaWNhdGlvbiB2aWEgYWRtaW4gCiAqIHBhbmVsIC0tIFNldHRpbmdzLT5Ob3RpZmljYXRpb25zIG1lbnUuICBUaGlzIGhlbHBzIHBvcHVsYXRlIHRoZSBzZWxlY3QgbGlzdCAKICogb2YgYXZhaWxhYmxlIG1lcmdlIGZpZWxkcy4KICoKICogQHJldHVybiBhcnJheSBBc3NvY2lhdHZlIGFycmF5IG9mIHRoZSBhdmFpbGFibGUgbWVyZ2UgZmllbGRzIGZvciB0aGlzIG5vdGlmaWNhdGlvbiBjb250cm9sbGVyLgogKi8KcHVibGljIGZ1bmN0aW9uIGdldF9tZXJnZV9maWVsZHMoKTphcnJheQp7CgogICAgLy8gU2V0IGZpZWxkcwogICAgJGZpZWxkcyA9IGFycmF5KCk7CgogICAgLy8gUmV0dXJuCiAgICByZXR1cm4gJGZpZWxkczsKCn0KICAgIAoKCi8qKgogKiBHZXQgbWVyZ2UgdmFyaWFibGVzLgogKgogKiBUaGlzIG9idGFpbnMgdGhlIG5lY2Vzc2FyeSBtZXJnZSB2YXJpYWJsZXMgZnJvbSB0aGUgZGF0YWJhc2UsIGFzIHlvdSBkZWZpbmVkIAogKiB3aXRoaW4gdGhlIGdldF9tZXJnZV9maWVsZHMoKSBmdW5jdGlvbiBvZiB0aGlzIGNsYXNzLiAgVGhlc2UgYXJlIHVzZWQgdG8gcGVyc29uYWxpemUgdGhlIAogKiBtZXNzYWdlIGZvciB0aGUgc3BlY2lmaWMgdHJhbnNhY3Rpb24gLyByZXF1ZXN0LgogKgogKiBAcGFyYW0gaW50ICR1c2VyaWQgVGhlIElEIyBvZiB0aGUgdXNlciBlLW1haWxzIGFyZSBiZWluZyBwcm9jZXNzZWQgYWdhaW5zdC4KICogQHBhcmFtIGFycmF5ICRkYXRhIEFueSBleHRyYSBkYXRhIG5lZWRlZC4KICoKICogQHJldHVybiBhcnJheSBBbiBhc3NvY2lhdGl2ZSBhcnJheSBvZiB0aGUgbWVyZ2UgdmFyaWFibGVzIHRvIHBlcnNvbmFsaXplIHRoZSBlLW1haWwgbWVzc2dhZSB3aXRoLgogKi8KcHVibGljIGZ1bmN0aW9uIGdldF9tZXJnZV92YXJzKGludCAkdXNlcmlkLCBhcnJheSAkZGF0YSk6YXJyYXkKewoKICAgIC8vIFNldCB2YXJzCiAgICAkdmFycyA9IGFycmF5KCk7CgogICAgLy8gUmV0dXJuCiAgICByZXR1cm4gJHZhcnM7Cgp9CgoKLyoqCiAqIEdldCByZWNpcGllbnQKICoKICogVXNlZCB3aGVuIHRoZSBub3RpZmljYXRpb24gY29udHJvbGxlciBzdXBwb3J0cyByZWNpcGllbnRzIC8gc2VuZGVycyBvdGhlciB0aGFuIHRoZSAKICogZGVmYXVsdHMgb2YgYWRtaW46WFggYW5kIHVzZXIuICBUaGlzIGZ1bmN0aW9uIHRha2VzIGluIHRoZSBzdHJpbmcgb2YgdGhlIHJlY2lwaWVudCAvIHNlbmRlciwgcGx1cyB0aGUgCiAqIElEIyBvZiB0aGUgdXNlciBub3RpZmljYXRpb25zIGFyZSBiZWluZyBwcm9jZXNzZWQgYWdhaW5zdCwgYW5kIHJldHVybnMgYW4gYXJyYXkgb2YgdGhlIGZ1bGwgbmFtZSBhbmQgZS1tYWlsICAKICogYWRkcmVzcyBvZiByZWNpcGllbnQgLyBzZW5kZXIuCiAqCiAqIEBwYXJhbSBzdHJpbmcgJHJlY2lwaWVudCBUaGUgcmVjaXBpZW50IHRvIG9idGFpbi4KICogQHBhcmFtIGludCAkdXNlcmlkIFRoZSB1c2VyIGlkIHRoYXQgZS1tYWlscyBhcmUgYmVpbmcgcHJvY2Vzc2VkIGFnYWluc3QuCiAqIEBwYXJhbSBhcnJheSAkZGF0YSBPcHRpb25hbCBhcnJheSBvZiBhbnkgbmVjZXNzYXJ5IGRhdGEuIAogKi8KcHVibGljIGZ1bmN0aW9uIGdldF9yZWNpcGllbnQoc3RyaW5nICRyZWNpcGllbnQsIGludCAkdXNlcmlkLCBhcnJheSAkZGF0YSA9IGFycmF5KCkpCnsgCgogICAgLy8gUmV0dXJuCiAgICByZXR1cm4gZmFsc2U7Cgp9Cgp9CgoK';

    // Properties
    public $display_name = 'Controller Name';

    // Set fields
    public $fields = array(

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
 * panel -- Settings->Notifications menu.  This helps populate the select list 
 * of available merge fields.
 *
 * @return array Associatve array of the available merge fields for this notification controller.
 */
public function get_merge_fields():array
{

    // Set fields
    $fields = array();

    // Return
    return $fields;

}
    


/**
 * Get merge variables.
 *
 * This obtains the necessary merge variables from the database, as you defined 
 * within the get_merge_fields() function of this class.  These are used to personalize the 
 * message for the specific transaction / request.
 *
 * @param int $userid The ID# of the user e-mails are being processed against.
 * @param array $data Any extra data needed.
 *
 * @return array An associative array of the merge variables to personalize the e-mail messgae with.
 */
public function get_merge_vars(int $userid, array $data):array
{

    // Set vars
    $vars = array();

    // Return
    return $vars;

}


/**
 * Get recipient
 *
 * Used when the notification controller supports recipients / senders other than the 
 * defaults of admin:XX and user.  This function takes in the string of the recipient / sender, plus the 
 * ID# of the user notifications are being processed against, and returns an array of the full name and e-mail  
 * address of recipient / sender.
 *
 * @param string $recipient The recipient to obtain.
 * @param int $userid The user id that e-mails are being processed against.
 * @param array $data Optional array of any necessary data. 
 */
public function get_recipient(string $recipient, int $userid, array $data = array())
{ 

    // Return
    return false;

}

}


