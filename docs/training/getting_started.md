
# Apex Training - Getting Started

First thing is first, visit the [Installation Guide](../install.md) and get an install of Apex up and running.
Once done, install a few base packages we will need with:

`php apex.php install webapp users transaction support devkit`


### Create Package

Next, we need to create our new package which we will call "training".  You can do this in terminal with:

`php apex.php create_package training`

When prompted to select a repository, enter 2 to specify the local repository that was installed with the
devkit package.  This will create our new package including two directories at:

- */src/training* -- Will hold the bulk of PHP code for this package.
- */etc/training* -- The configuration of this package.


### Package Configuration

The new file located at */etc/training/package.php* is the main configuration file for our package, and is explained in full on the 
[Package Configuration](../packages_config.md) page of the documentation.  The `__construct()` method is the main 
method within this file, and can contain various arrays as summarized below.

Array | Description 
------------- |------------- 
`$this->config` | Key-value pair of all configuration values used by the package, and their default value upon installation.
`$this->hash` | An array with the key being the name of the hash, and the value being an associative array of key-value pairs of all variables / options within the hash.  Define any sets of options for select / radio / checkbox lists in this array. 
`$this->menus` | An array of arrays, and defines all menus that are included in the package within the administration panel, member's area, and public web site. 
`$this->ext_files` | Any external files included in this package, which are not components. 
`$this->placeholders` | Allows you to place `<a:placeholder>` tags within member area / public templates, which then are replaced with the contents defined by the administrator via the CMS->Placeholders menu of the admin panel.  
`$this->boxlists` | Used to add entries / define lists of settings.  For example, Settings->Users and Financial menus of the admin panel are examples of boxlists. 
`$this->notifications` | Allows you to have default e-mail notifications created upon package installation, which are managed via the Settings->Notifiations menu of the administration panel.
`$this->dashboard_teism` | Defines the various dashboard items / widegts that are available within this package.

Now that we have the basic gist of this method, open up the */etc/training/package.php* file, and change
the `__construct()` method to:

~~~php

public function __construct()
{

// Config variables
$this->config = array(
    'daily_award' => 50
);

// Hash
$this->hash = array();
$this->hash['status'] = array(
    'pending' => 'Pending', 
    'complete' => 'Competed', 
    'rollover' => 'Rolled Over'
);

// Menus -- Admin Panel -- Lottery menu
$this->menus = array();
$this->menus[] = array(
    'area' => 'admin', 
    'type' => 'parent', 
    'position' => 'after financial', 
    'icon' => 'fa fa-fw fa-card', 
    'alias' => 'lottery', 
    'name' => 'Lottery', 
    'menus' => array(
        'manage' => 'Manage Lotteries'
    )
);

// Menus -- Admin Settings
$this->menus[] = array(
    'area' => 'admin', 
    'parent' => 'settings', 
    'position' => 'bottom', 
    'alias' => 'lottery', 
    'name' => 'Lottery'
);

// Menus -- Member Area
$this->menus[] = array(
    'area' => 'members', 
    'parent' => 'financial', 
    'position' => 'top', 
    'alias' => 'lottery', 
    'name' => 'View Lotteries'
);

}

~~~


### Scan Package

We will explain the above code in detail just below, but every time you modify a package.php file, you must scan the
package to update the database as necessary.  In terminal, simply type:

`php apex.php scan training`

Once done, if you login to either the administration panel or member's area, you will see the new menus we
added.


### __construct() Code Explained

The rest of this page explains the above code in detail, but for full information on the `__construct()`
function within the package.php file, please visit the [Package Configuration](../packages_config.md) page 
of the documentation.


##### `$this->config`

We added one configuration variable, which can be accessed anywhere within the software with:

~~~php
$amount = app::_config('training:daily_award');
~~~with:

You may also update the value of the configuration variables anywhere within the software by using the
app::update_config_var() method such as:

~~~php
$award = 50;
app::update_config_var('training:daily_award', $award);
~~~


##### `$this->hash`

We added one hash for the lottery status, which will be used later to easily populate select lists.


##### `$this->menus`

As stated in the documentation, this is an array of associative arrays that defines the various menus to add
for this package, and should be quite straight forward.  In the above code, we added one sub-menu under
the Settings menu within the administration panel, plus adding one parent menu with one sub-menu in the
administration panel, plus one sub-menu into the member's area for users to view lotteries.

### Next

Now that our new package is created with some base configuration, let's move to the next step, [Create
Database Tables](create_database.md).


