
# Views - ReCaptcha Integration

Apex allows you to easily take advantage of the popular Google noCaptcha reCaptcha, which allows you to help
keep bots and spam at bay while users only need to click on a checkbox without having to decipher the
characters on a scrambled image.

To add reCaptcha to any of the pages within the system, complete the following steps:

1. Visit the [Google reCaptcha API](https://www.google.com/recaptcha/admin) page, and obtain an API key-pair for your site.  Within the Settings->General menu of the administration panel, enter your site and secret API keys in the first General tab.
2.  Within any of the TPL template files, simply place the `<a:recaptcha>` HTML tag.  This will be replaced with the small widget that contains the checkbox users must click.
3.  Within your PHP code, authenticate reCaptcha by simply using the auth::recaptcha() function, which returns a boolean of true or false depending whether or not the user was verified.


**Example**

Again, within any TPL template file simply add the `<a:recaptcha>` tag.  Then inside the proceeding PHP code,
it will look something like:

~~~php
namespace apex;

use apex\app;
use apex\libc\auth;
use apex\libc\view;

if (!auth::recaptcha()) {
    view::add_callout("Unable to verify that you are a human.  Please try again", 'error');
    app::set_uri('previous_page', false, true);
}
~~~



