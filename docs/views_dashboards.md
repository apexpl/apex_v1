
# Views - Dashboards

Dashboards are the first / home page of the administration panel and member's area.  Through the Settings->Dashboards 
menu of the administration panel, you can define the various items that will display on each dashboard.  There is one 
default dashboard for the admin panel, one default for the member's area, plus each administrator can have a customized dashboard.


### Dashboard Layout

There are three sections to each dashboard:

* Top Items -- Usually the larger boxes / buttons, approx 350x350px in size, and give quick statistics such as number of users, revenue earned, etc.
* Right Sidebar -- Used to give more textual summary information.
* Tab Page -- Dashboards also contain a standard tab control for things such as pending support tickets, transactions, etc.


### Dashboard HTML Snippets

The HTML snippets for dashboards can be found in the */views/themes/THEME_ALIAS/tags.tpl* file of each theme.  There 
is a "dashboard" section there, which should be quite self explanatory.


### Defining Dashboard Items

While developing packages, you may want to define your own dashboard items that are available within the 
dashboards.  This can be done by defining the `$this->dashboard_items` array within the 
*/etc/PACKAGE/package.php* file for the package.  Within the __construct method you can defing this array, which is 
simply an array of associative arrays.  The associatve arrays have the following key-value pairs:

Key | Description
------------- |------------- 
area | The area, will always be "admin" or "members".
type | Must be either, "top", "right", or "tab".
is_default | Optional, and a 1 or 0 defining whether this is a default item.  If 1, it will be activated on the default dashboards upon package installation.
divid | Value is replaced with the ~divid~ merge field within the HTML snippet of the tags.tpl file.
panel_class | Value is replaced with the ~panel_class~ merge field within the HTML snippet of the tags.tpl file.
alias | The alias of the item.  Used within the PHP code to call it (see below).
title | Title of the item, alow replaced with any occurrencies of the ~title~ merge field within the HTML snippet.
description | Description of the item.


For example, within the package.php file you could have:

~~~php

$this->dashboard_items = array(
    array(
        'area' => 'admin', 
        'type' => 'right', 
        'is_default' => 1, 
        'divid' => 'members-online',  
        'panel_class' => 'panel bg-teal-400', 
        'alias' => 'total_revenue', 
        'title' => 'Revenue Summary', 
        'description' => 'Provides a quick summary of all revenue earned.'
    )
);
~~~


Then as always, we need to scan the package again in order to add the dashboard item to the database.  Within terminal type:

`./apex scan my_package`


Last, we need to add the necessary PHP code.  To do this you must create a 'dashboard' library within your package, with for example:

`./apex create lib my_package:dashboard`

This will create a new file at */src/my_package/dashboard.php* which needs one method added to it that will return the contents of 
our dashboard item.  The name of the method must be AREA_TYPE_ALIAS, so in the above example, we would have:

~~~php
<?php
declare(strict_types = 1);

namespace apex\my_package;

use apex\app;
use apex\libc\db;
use apex\libc\redis;
use apex\libc\debug;


class dashboard
{

    public function admin_right_revenue()
    {

        // Get and return the contents...
        $contents = 'revenue stats here';
        return $contents;

    }

}

~~~

That's it, done, and the new dashboard item is fully developed and operational.  It will be included in your package upon publishing to a repository.


##### Tab Page Items

For any tab page items created, you must also create a tab page within the "core:dashboard" tab control.  Again, ,alias of the tab page must be AREA_ALIAS.  For 
example, if you created a tab page named "support_tickets", you would create a tab page with:

`./apex create tabpage core:dashboard:admin_support_tickets my_package`

Then simply develop the tab page as normal, and that's it.






