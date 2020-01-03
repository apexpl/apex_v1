
# Views - PHP Methods

There are a few PHP methods that you will need when developing views to assign merge variables, add callout
messages, and so on.  These are available via the *apex\libc\view* service, meaning they are
statically available, and are explained below.


### view::assign(string $var_name, mixed $value)

**Description:** Assigns a merge variable to the system, allowing you to personalize the view
with any necessary information.  The `$value` can be either a string, array, or associative array in cases of
`<a:section>` tags.  Within the TPL code, you can then place merge fields such as `~full_name~`, and it will
be replaced with the value of the variable with the `$var_name` of "full_name".

When you assign an array as the value, you use merge fields withi the TPL code such as
`~array_name.var_name~`.  For example, the app::_config() array is always present as a template
variable, and you can use merge fields such as `~config.core:site_name~`.


### view::add_callout(string $message, string $type = 'success')

**Description:** Adds a callout message to the next view that is displayed.  These are the standard success /
error / warning messages that are displayed at the top of the page.  The `$type` variable defaults to "success", but can also be "error",
"info" or "warning".

**Example**

~~~php
namespace apex;

use apex\app;
use apex\libc\view;

view::add_callout(tr("Successfully added new blog post, %s", $title));

view::add_callout("You did not specify a blog title", 'error');
~~~


### app::set_uri(string $uri)

**Description:** Changes the template that will be displayed to the web browser, and is only needed if you
ever want to force a different template to be displayed other than what's in the URI.  For example, if you
would like to display the login.tpl template, you would use:

`app::set_uri('login');`


### view::parse()

Generally never needed unless for some reason you need to halt execution of all other code, and immediately
display a template.  Simply set it as the response contents with:

`app::set_res_body(view::parse());`


### `app::echo_template
