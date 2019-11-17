
# Apex Training - Admin Manage Lotteries View

Last aspect of the actual development is the Lottery->Manage Lotteries menu within the admin panel that we defined within the 
*/etc/training/package.php* file.  Within terminal, type:

`./apex create view admin/lottery/manage training`

This will create two new files at the below locations, which are assigned to the "training" package, hence will 
be included upon publishing the package to a repository.

* /views/tpl/admin/lottery/manage.tpl
* /views/php/admin/lottery/manage.php


### .tpl File

Open the file at */views/tpl/admin/lottery/manage.tpl* and enter the following contents:

~~~html

<h1>Manage Lotteries</h1>

<a:form>

<a:box>
    <a:box_header title="Recent Lotteries"">
        <p>Below shows all recent lotteries that have been won.</p>
    </a:box_header>

    <a:form_table>
        <a:ft_custom label="Total Lotteries" contents="~total_lotteries~">
        <a:ft_custom label="Total Amount Paid" contents="~total_amount~">
    </a:form_table>


    <a:function alias="display_table" table="training:lotteries">
</a:box>

~~~

This is a simple view that contains one container / panel, which shows the totals, and a table of all recent lotteries.


### .php File

Open the file at */views/php/admin/lottery/manage.php*, and enter the following contents:

~~~php
<?php
declare(strict_types = 1);

namespace apex\views;

use apex\app;
use apex\svc\db;
use apex\svc\view;
use apex\svc\debug;

/**
 * All code below this line is automatically executed when this template is viewed, 
 * and used to perform any necessary template specific actions.
 */

// Get totals
$total = db::get_field("SELECT count(*) FROM lotteries");
$total_amount = db::get_field("SELECT sum(amount) FROM lotteries");

// Zero variables as needed
if ($total == '') { $total = 0; }
if ($total_amount == '') { $total_amount = 0; }

// Template variables
view::assign('total_lotteries', $total);
view::assign('total_amount', fmoney((float) $total_amount));

~~~

This PHP file simply grabs the total number of lotteries and total amount from the database, and assigns them as template variables.


### Next

Now that development is complete, we need to ensure everything works properly, so let's move on 
by [Creating a Unit Test](unit_test.md).






