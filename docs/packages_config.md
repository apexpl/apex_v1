
# Package Configuration

Every package has a configuration file located at */etc/PACKAGE_ALIAS/package.php*, which includes a
`__construct()` function.  This function populates various arrays that define the configuration of the
package, such as configuration variables, menus, external files, and more.  The below sections explain each of
the arrays supported within this function.

1. <a href="#config">`$this->config`</a>
2. <a href="#hash">`$this->hash`</a>
3. <a href="#ext_files">`$this->ext_files`</a>
4. <a href="#menus">`$this->menus`</a>
5. <a href="#placeholders">`$this->placeholders`</a>
6. <a href="#boxlists">`$this->boxlists`</a>
7. <a href="#notifications">`$this->notifications`</a>
8. <a href="#dependencies">`$this->dependencies`</a>
9. <a href="#composer_dependencies">`$this->composer_dependencies`</a>


<a name="config"></a>
### `$this->config`

A simple array containing key-value pairs that define the various configuration variables for this package.
They keys are the variables themselves, and the values are the default value they variable is set to upon
installation.  All variables are then accessible via the app::_config() method.

For example, if develping a package named "myblog":

~~~php

$this->config = array(
    'title' => 'Your Blog Name',
    'status' => 'active'
);
~~~

The above variables can then be easily accessed anywhere within the code via
app::_config('myblog:title') and app::_config(myblog:status').  When updating the configuration
variables (ie. within the administration panel), always use the app::update_config_var($var, $value) method 


<a name="hash"></a.
### `$this->hash`

This array contains arrays that define the various hashes that will be used by this package.  These are
generally lists to easily populate select, radio, and checkbox lists within the software using the
[hashes class](https://apex-platform.org/api/classes/apex.app.utils.hashes.html), and within the Apex form component.

The keys of this array are the alias of the hash itself, and the values are arrays that consist of key-value
pairs defining the various items in each hash / list.  For example:

~~~php
$this->hash = array(
    'post_status' => array(
        'active' => 'Active',
        'pending' => 'Pending',
        'retracted' => 'Retracted',
        'archived' => 'Archived'
    ),
    'post_type' => array(
        '1' => 'Standard',
        '2' => 'Guest Post',
    '3' => 'Highlighted'
    )
);
~~~

Within the `<a:` tags and form components, you can now use the database `hash:myblog:post_status` and
`hash:myblog:post_title`, and the appropriate lists will be populated with the lists defined above.

<a name="ext_files"></a>
### `$this->ext_files`

Allows you to add external files to the package, which reside outside of the standard Apex structure.  For
example, if you need to place additional files within the /public/ directory, you would define them in this
array.  This is a simple one-dimenstional array, giving a list of all external files included in this package,
relative to the installation directory.  For example:

~~~php
$this->ext_files = array(
    'public/plugins/some_cool_script.js',
    '/public/plugins/mylibrary/*'
);
~~~

When this package is compiled and published, the */public/plugins/come_cool_script.js*, and the directory
including all contents at */plugins/mylibrary/* will be included.

<a name="menus"></a>
### `$this->menus`

This array is a little more complex, and allows you to define all the menus this package contains within the
administration panel, member's area, and public web site.  This is a one-dimensional array of associative arrays, and for
example:

~~~php
$this->menus = array();
$this->menus[] = array(
    'area' => 'admin',
    'type' => 'header',
    'position' => 'bottom',
    'alias' => 'hdr_myblog',
    'name' => 'My Blog'
);

$this->menus[] = array(
    'area' => 'admin',
    'position' => 'top hdr_myblog',
    'type' => 'parent',
    'icon' => 'fa fa-fw fa-post',
    'alias' => 'Posts',
    'name' => 'Posts',
    'menus' => array(
        'new' => 'Add New Post',
        'viewall' => 'View All Posts',
        'search' => 'Search Posts'
    )
);

$this->menus[] = array(
    'area' => 'admin',
    'parent' => 'settings',
    'position' => 'bottom',
    'type' => 'internal',
    'alias' => 'myblog',
    'name' => 'Blog Settings'
);
~~~

For readability sake, we started with a blank array, then pushed three arrays into it. All three arrays
pertain to the administration panel, and add the following menus:

* First adds a "My Blog" seperator / header.
* Second adds a parent menu named "Posts" that contains three sub-menus.
* third will add a Settings->Blog Settings menu. 

The below table describes the variables that are supported within this array.

Variable | Required | Description 
------------- |------------- |------------- 
area | Yes | The area where the menu will be placed, and is basically always "admin", "members" or "public", although can be expanded on by other developers. 
type | No | The type of menu. This can be "header" for a header / seperator, "parent" for a parent drop-down menu with sub-menus, "internal" for a standard menu, or "external" for a menu that links to an outside URL.  Defaults to "internal".
parent | No | Only required for type "internal" menus, and when there is an existing parent menu you'd like to place the sub-menu into. As shown in the above example, we added the 'BLog Settings' sub-menu into the existing Settings parent menu. 
position | No | The position of the menu, defaults to "bottom".  Please see the below section for details on this variable. 
icon | No | Generally only used for parent menus, and is the icon to display for the menu.  Generally from FontAwesome icons, and is generally included within a `<i class="ICON"></i>` tag within the menus. 
alias | Yes | The alias of the menu, should be alpha-numeric and all lowercase.  This is used as the URI to the menu and template within the /views/tpl/ directory. 
name | Yes | The name of the menu that is displayed within the web browser. 
menus | No | Only used for parent menus, and is an array containing key-value pairs of sub-menus to create within the parent drop down menu.  In our above example, we added three sub-menus to our parent menu. 
require_login | no | Only useful for the public site, and is a 1 or 0 defining whether or not the user must be authenticated for this menu to be visible.  Used for menus such as "my Account" or "logout" within the public site. 
require_nologin | No | Only useful for the public site, and is a 1 or 0 defining whether the user must NOT be authenticated for this menu to be visible. For example, you would use this to hide the "Login" and "Register" menus if the user is already logged in.


##### `'position'` Variable

This variable defines the position of the menu, and is relative to the "parent" variable. If no "parent" is
defined, then relative to the top-level menus of the area (admin, members, public).  It can be any of the
following:

Variable | Description 
------------- | ------------- 
`top` | Will become the first menu displayed, so if "parent" is defined will be the first sub-menu within the parent drop-down menu, and otherwise will be the first top-level menu displayed. 
`bottom` | Same as `top`, except will be the last menu relative to "parent", or otherwise the last menu displayed within the top-level menus. 
`top|bottom HEADER` | Only useful for menus of the type "parent", and lets you specify the menu should be displayed at the top or bottom just after a menu of the type "header". 
`before ALIAS` | Will place the menu just before / above the menu with the alias `ALIAS`. 
`after ALIAS` | Will place the menu just after / below the menu with the alias `ALIAS`.


<a name="placeholders"></a>
### `$this->placeholders`

**Description:**  Allows you to place `<a:placeholder>` tags anywhere within the TPL template files, and have
them replaced with any value defined by the administrator via the CMS->Placeholders menu of the admin panel.
This is done to help allow administrators to add additional text / contents to the templates without manually
modifying the TPL code. Many times when upgrades are released, they will include some of the public / member
area TPL templates, so upon installing them, any manual changes made to the TPL are overwritten.

This array is simply a key-value pair, the keys being any URI )eg/ public/login,
members/account/update_profile, etc.), and the value is an array containg all placeholder aliases you would
like to create.  The aliases can be anything you wish.  For example:

~~~php
$this->placeholders = array(
    'public/login' => array('above_form', 'below_form'),
    'public/register' => array('above_form', 'below_form')
);
~~~

When this package is installed, the administrator can then define the values they desire for those four
placeholders via the CMS->Placeholders menu of the admin panel.  To add the placeholders into the templates,
simply use tags such as:

~~~
<a:placeholder alias="above_form>
<a:placeholder alias="below_form>
~~~

Simple as that.  Now when viewing the template, all placeholder tags will be replaced with whatever value the
administrator has defined for that placeholder.


<a name="#boxlists"></a>
### `$this->boxlists`

Rarely used, but useful if you have a large number of settings for the package that need to be split into
multiple pages.  For an example of box lists, visit the Settings->Users and Settings->Financial menus of
the administration panel.  This allows you to easily place an expandable list of items on a page that link to
other pages, and also allows you to easily add an item into an existing box list from another package.

For example, to add an existing item to the Settings->Users page, you would use:

~~~php
$this->boxlists = array();
$this->boxlists[] = array(
    'alias' => 'users:settings',
    'href' => 'admin/settings/users_someapi',
    'title' => 'Some API Settings',
    'description' => 'Configure the API so your users can take advantage of all the cool features.'
);
~~~

When the package is installed, it will add the above item to the `users:settings` box list, which is displayed
within the Settings->Users menu.  Creating new box lists is just as simple, and for example:

~~~php
$this->boxlists = array(
    array(
        'alias' => 'myblog:settings',
        'href' => 'admin/settings/myblog_general',
        'title' => 'General Settings',
        'description' => 'Configure the general settings for the blog package.'
    ),
    array(
        'alias' => 'myblog:settings',
        'href' => 'admin/settings/myblog_deleteall',
        'title' => 'Delete All Posts',
        'description' => 'Go here if you wish to delete all blog posts, and start your blog fresh.'
    )
);
~~~

That's it.  Now within any template, you can display the box list with the two above items (and any other
items future packages add) by placing the HTML tag:

`<a:boxlist alias="myblog:settings">`



<a name="notifications"></a>
### `$this->notifications`

An array of associatve arrays, and allows you to define default e-mail notifications that are created upon
installation of the package.  For example, upon installing the "users" package, default e-mail notifications
are created that are sent upon registration, reset password request, e-mail verification, and so on.  Each
element of this array is an associative array with the following key-value pairs:

Variable | Description 
------------- |------------- 
controller | The controller alias of the e-mail notification (ie. filename within /src/core/controller/notifications/ directory without the .php extension).
sender | The sender of the e-mail (eg. admin:1, user, etc.) 
recipient | The recipient of the e-mail (eg. admin:1, user, etc.) 
content_type | Either "text/plain" or "text/html". 
subject | the subject of the e-mail notification
contents | A BASE64 encoded string of the contents of the e-mail message. 
cond_XXX | Any condition values needed for the e-mail controller.  Check the `$fields` array properly within the PHP class of the email notification for available fields.  For example, if a field is named"action", you would add a key with the name "cond_action", and the value being whatever necessary.


For example, the below code will add a e-mail notification that is sent to the user upon registration.

~~~php
$this->notifications = array();
$this->notifications[] = array(
    'controller' => 'users',
    'sender' => 'admin:1',
    'recipient' => 'user',
    'content_type' => 'text/plain',
    'subject' => "Thank you for registering, ~username~',
    'contents' => 'BASE64 STRING',
    'cond_action' => 'create',
    'cond_status' => '',
    'cond_group_id' => ''
);
~~~



<a name="dependencies"></a>
### `$this->dependencies`

A simple linear array that lists the package aliases of all dependencies for this package.  For example, 
if the "users" package is required for this package to run correctly, you would put:

~~~php
$this->dependencies = array(
    'users'
);
~~~



<a name="composer_dependencies"></a>
### `$this->composer_dependencies`

An associative array that allows you to define additional composer dependencies that are required for tihs package.  Same as 
the composer.json file, the keys are the package name, and the values are the version requirement.  Upon installation of the package, the composer.json file will be updated 
accordingly, although you will need to manually run "composer update".  For example:

~~~php
$this->composer_dependencies = array(
    'some/package' => '>=2.5.0', 
    'another/package' => '*'
);
~~~






