
# Apex Training - Admin Settings View

Within our */etc/training/package.php* file, we defined a Settings->Lottery menu within the administration panel, 
to allow the administrator to define the daily lottery award.  Let's go ahead and develop that menu, so within terminal type:

`./apex create view admin/settings/lottery training`

This will create two new files at:

* /views/tpl/admin/settings/lottery.tpl
* /views/php/admin/settings/lottery.php


### .tpl File

Open the new file at */views/tpl/admin/settings/lottery.tpl* and enter the following contents:

~~~html

<h1>Lottery Settings</h1>

<a:form>

<a:box>
    <a:box_header name="Settings">
        <p>Make any desired changes to the lottery settings below, and submit the form to save the changes.</p>
    </a:box_header>

    <a:form_table>
        <a:ft_amount name="daily_award" value="~config.training:daily_award~">
        <a:ft_submit value="update" label="Update Lottery Settings">
    </a:form_table>

</a:box>
~~~

This defines a simple page, with one container / panel that contains one form with a single textbox allowing the 
administrator to define the daily lottery award.  As you still notice, the UTI within your web browser when visitng the menu is */admin/settings/lottery*, which 
is the same as the file location on the server.  For more information on the .tpl files and speical HTML tags, please visit:

* [Views -- General](../views.md)
* [Special HTML Tags](views_tags.md)
* [HTML Forms (`<a:form_table>`)](views_forms.md)


### .php file

Every view also has an associated .php file that is executed upon viewing the page.  Open the file at */views/php/admin/settings/lottery.php*, and 
enter the following contents:

~~~php
<?php
declare(strict_types = 1);

namespace apex\views;

use apex\app;
use apex\libc\db;
use apex\libc\view;
use apex\libc\debug;

/**
 * All code below this line is automatically executed when this template is viewed, 
 * and used to perform any necessary template specific actions.
 */


// Update settings
if (app::get_action() == 'update') { 

    // Update config
    app::update_config_var('training:daily_award', app::_post('daily_award'));

    // User message
    view::add_callout('Successfully updated general lottery settings');

}

~~~

This is a simple PHP file that simply checks the app::get_action() method to see whether or not the 
submit button was pressed, and if so, updates the one configuration variable via the app::update_config_var() method.


### Next

Before we quickly develop the other two views for the menus we defined, let's first [Create the Data Table](data_table.md) that will be displayed on those pages.



