
# Member Area View

Within the */etc/training/package.php* file we defined one menu in the member's area at Financial->View Lotteries, so let's go ahead and 
developm that view now.  Within terminal, type:

`php apex.php create view members/financial/lottery training*

This will create two new files at the below locations, and assign them to the "training" package, so they are 
included when the package is published to a repository.

* /views/tpl/members/financial/lottery.tpl
* /views/php/members/finanncial/lottery.php


### .tpl File

Open the file at */views/tpl/members/financial/lottery.tpl*, and enter the following contents:

~~~html

<h1>View Lotteries</h1>

<a:tab_control>

    <a:tab_page name="Recent Lotteries">
        <h3>Recent Lotteries</h3>

        <p>The below table lists all recent lottery wins.</p>

        <a:function alias="display_table" table="training:lotteries">
    </a:tab_page>

    <a:tab_page name="Your Wins">
        <h3>Your Wins</h3>

        <p>The below table lists all lotteries you have personally won.</p>

        <a:function alias="display_table" table="training:lotteries" userid="~userid~">
    </a:tab_page>

</a:tab_control>
~~~

This defines a page with a simple tab control that contains two tabs, one for all recent lottery wins, and one for the user's personal lottery 
wins.  


### Next

We don't need to write any PHP code for this view, and the .tpl file above will run just fine if you visit the Fiancnail->View Lotteries menu 
of the member's area.  Let's move on to the [Admin Manage Lotteries View](admin_manage_lotteries_view.md).


