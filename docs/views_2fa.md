
# Views - 2FA via e-mail / SMS

Apex allows for very easy and efficient 2FA authentication via both, e-mail and SMS.  With a simple one line
of code you can implement 2FA authentication anywhere within your code. Upon being authenticated, the exact
request they were previously at will continue, including all POSTed form fields.  This is useful for sensitive
operations such as sending / withdrawing funds, etc.


### auth::authenticate_2fa()

This is a general purpose 2FA authentication method, will check both global and user settings, then
authenticate as necessary depending on the settings.  Generally, this is the only function you need to use to
ensure a user is authenticated via 2FA.

**Example**

~~~php
namespace apex;

use apex\app;
use apex\libc\auth;

// Withdraw funds
if (app::get_action() == 'withdraw') {

    // Make sure we're authenticated
    auth::authenticate_2fa();

    // We know we're authenticated via 2FA, go ahead and withdraw funds.

}
~~~


### auth::authenticate_2fa_email()

This function can be used to force 2FA authentication via e-mail regardless of global or user settings.  It will display a 
template stating an e-mail has been sent to them, and will not continue with the process until the link within the e-mail has been clicked on.


### auth::authenticate_2fa_sms()

This function can be used to force authentication via SMS regardless of global or user settings.  This will
send a 6 digit code via SMS with the Nexmo API to the user's phone number, and display a template requireing
they enter the code before continuing.


