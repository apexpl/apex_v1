
# `apex` CLI Commands

Within the installation directory Apex comes with both, an apex.php script and an `apex` phar archive.  Both are
the exact same, but you may move the `apex` phar archive within your environment path (ie. /usr/bin/) allowing
you to simply type "apex" instead of "php apex.php" to perform CLI commands.

Various CLI commands are built into Apex which help facilitate development and package / upgrade / theme
management.  You can view a list of all availble commands by typing "apex.php help" within terminal, and below
explains all available commands.

1. <a href="#general">General (package / upgrade / theme management)</a>
2. <a href="#package">Package / Upgrade Development</a>
3. <a href="#component">Component Development</a>
4. <a href="#system">System Maintenance</a>


<a name="general"></a>
## General Commands

Various general CLI commands are available allowing you to search all repositories for packages, install and
upgrade packages / themes, plus more.  Below describes all general CLI commands available.

#### `list_packages`

**Description:** Lists all packages available to the system from all repositories configured on the system.

**Usage:** `php apex.php list_packages`


#### `search TERM`

**Description** Searches all repositories configured on the system for a specific search term.

**Example:** `php apex.php search mailing list`


#### `install PACKAGE1 PACKAGE2...`

**Description:** Downloads a package from a repository, and installs it on the system. This command allows you
to specify multiple packages at one time as well.

**Example:** `php apex.php install webapp users transaction support`


#### `upgrade [PACKAGE]`

**Description:** Checks the repositories, and automatically downloads and installs any available upgrades.  if
desired, you may optionally specify a single package to upgrade. Otherwise, all installed packages will be
upgraded.  It's recommended to run this command about once a week to ensure your packages are always up to
date.

**Example:** `php apex.php upgrade`


#### `check_upgrades [PACKAGE]`

**Description:** Checks the repositories for any available upgrades, and provides information on the latest
available version of each package.  This does not download or install the upgrades, and instead only provides
what upgrades are available.

**Example:** `php check_upgrades`


#### `list_themes`

**Description:** Lists all themes available from all repositories configured on this system.

**Example:** `php apex.php list_themes`


#### `install_theme THEME_ALIAS`

**Description:** Downloads the specified theme from the repository, and installs it on the system.

**Example:** `php apex.php install_theme mydesign`


#### `change_theme AREA THEME_ALIAS`

**Description:** Allows you to change the currently active theme on an area.  The `AREA` should be either
"public" or "members", and then the alias of the theme you would like to activate.  The theme must already be
installed on the system.

**Example:** `php apex.php change_theme public mydesign`



<a name="package"></a>
## Package / Upgrade Development

Various CLI commands are available allowing you to easily create, publish, and delete packages and upgrades
within repositories.  Below explains all CLI commands available for package / upgrade development.


#### `create_package PACKAGE [REPO_ID]`

**Description:** Creates a new package on the local system, which can then be developed, and later published
to a repository.  This creates the necessary directories within /etc/ and /src/, and upon creation, you can
begin creting components on the package via the apex.php script.

**Example:** `php apex.php create_package casino`


#### `scan PACKAGE`

**Description:** Scans the */etc/PACKAGE/package.php* configuration file, and updates the database as needed.
Use this during development, after you have updated the package.php file with new information such as config
variables or menus, run this to reflect the changes within the database and system.

**Example:** `php apex.php scan casino`


#### `publish PACKAGE`

**Description:** After developing a package, you may publish it to the repository using this command.  This
will compile the package, and upload it to the repository,.  Once done, you can then begin installing the
package on other systems via the `install PACKAGE` command.

**Example:** `php apex.php publish casino`


#### `delete_package PACKAGE`

**Description:** Completely removes a package and all of its components from the system. Please note, this
does not remove the package from any repositories it has been previously uploaded to, and only removes it from
the local system.

**Example:** `php apex.php delete_package casino`


#### `create_upgrade PACKAGE [VERSION]`

**Description:** Creates a new upgrade point on the specified package.  You may optionally define a version
for the upgrade, and if left unspecified, the system will simply increment the third element of the current
version by one.  This will basically create an "image" of the package by creating a SHA1 hash of every file,
and upon publishing the package, these hashes will be checked allowing the system to determine all
modifications made to the package since the upgrade point was created.  This allows for virtually hands free
version control.

**Example:** `php apex.php create_upgrade casino`


#### `publish_upgrade [PACKAGE]`

**Description:** Once you've completed developing an upgrade after creating the upgrade point, you can upload
it to the repository using this command.  Once uploaded, all systems with the package installed can install
the upgrade on their system with the `apex.php upgrade` command.

**Example:** `php apex.php publish_upgrade casino`


#### `create_theme THEME_ALIAS`

**Description:**  Creates a new theme, which can then later be published to a repository if desired.  This
creates the necessary directory structure within the /views/themes/ and /public/themes/ directories, from
which you can begin creating the theme.

**Example:** `php apex.php create_theme mydesign`


#### `publish_theme THEME_ALIAS`

**Description:** Will publish the specified theme to the repository.  Once published, it can be installed on
any system with the `apex.php install_theme THEME_ALIAS` command.

**Example:** `php apex.php publish_theme mydesign`


#### `delete_theme THEME_ALIAS`

**Description:** Deletes the specified theme from the system, including all files and directories.  Please
note, this only deletes the theme from your local system, and not any repositories.

**Example:** `php apex.php delete_theme mydesign`



<a name="component"></a>
## Component Development

There are a couple commands you will be using very frequently during development to create and delete
components, which are explained below.  Always ensure to create all components via the apex.php script, and
NEVER manually create the files as they will not get registered to the package for publication.  For details
on all components supported by Apex, please visit [List of Components](packages.md).


#### `create TYPE PACKAGE:[PARENT:]ALIAS [OWNER]`

**Description:** Creates a new component on the system.  Creating new components must adhere to the following
rules:

* The `TYPE` variable is the type of the component, and must be one of the following:

    * ajax
    * autosuggest
    * cli
    * controller
    * cron
    * form
    * htmlfunc
    * lib
    * modal
    * tabcontrol
    * tabpage
    * table
    * test
    * view
    * worker
* The `PARENT` variable is only required for the component types of `controller` and `tabpage`.  For "tabpage" components, naturally this needs to be the alias of the tab control to place the new tab page into.  For the type "controller", this can be left blank, and it will create a new controller parent (eg. directory within /src/PACKAGE/controller/), otherwise is the parent controller to place the new controller into.
* The `OWNER` variable is required for the types "controller", "tabpage", and "view".  This is the package who owns the component, as these components can and will end up in a different pacakges /src/ directory.  Upon publishing, this component will be included in the `OWNER` package.
* The `OWNER` variable is also required for the "worker" component type, but instead of being the owner package, is the routing key for the worker.  This defines which messages to route to the worker.  For more information, please visit the [worker Component](components/worker.md) page of this manual.
* For the "view" component type, the `PACKAGE:PARENT:ALIAS` element of the command is simply the URI of the new template (eg. admin/users/some_page)

**Example (lib:** `php apex.php create lib casino:games`

**Example (view:** `php apex.php create view admin/games/bets casino`

**Example (worker):** `php apex.php create worker casino:user users.user`

**Example (controller):** `php apex.php create controller core:http_requests:games casino`


#### `delete TYPE PACKAGE:[PARENT:]ALIAS`

**Description:** Deletes a component from the system.  The two variables passed are the exact same as the
`create` command above.  This will permanently remove the component from the filesystem and database.

**Example (lib):** `php apex.php delete lib casino:games`

**Example (view):** `php apex.php delete view admin/casino/bets`

**Example (controller):** `php apex.php delete controller core:http_requests:games`


#### `scan PACKAGE`

**Description:** Scans the */etc/PACKAGE/packge.php* configuration file, and updates the database as needed.
Use this during development, after you have updated the package.php file with new information such as config
variables or menus, run this to reflect the changes within the database and system.

**Example:** `php apex.php scan casino`



<a name="system"></a>
## System Maintenance
    
There are several CLI commands available for general system maintenance, such as addinf / updating
repositories, updating connection information for both mySQL master database and RabbitMQ, and more.  Below
explains all system maintenance commands available.


#### `add_repo HOSTNAME [USERNAME] [PASSWORD]`

**Description:** Adds a new repository to the system, which is then checked for available packages, themes and
upgrades.  The HOSTNAME is the base hostname of the repository, and you may optionally specify a username and
password in case of a private repository, which will be provided to you by the owner of the repository.

**Example:** `php apex.php add_repo repo.somevendor.com myuser mypassword`


#### `update_repo HOSTNAME`

**Description:** Allows you to update an existing repository already in the system with a username and
password.  Specify the hostname of the repo, and assuming it is already configured on the system, you will be
prompted for a username and password, which will be provided to you by the owner of the repository.  This is
to gain access to any private packages you may have purchased.

**Example:** `php apex.php update_repo repo.somevendor.com`


#### `mode [DEVEL|PROD] [DEBUG_LEVEL]`

**Description:** Changes the server mode between either development or products, and allows you to change the
debug level between 0 -5.

**Example:** `php apex.php mode devel 4`


#### `debug NUM`

**Description:** Specifies whether or not to save debugging information for the next requests, which can then
be viewed through the Dev Kit->Debugger menu of the administration panel.  The value passed must be one of the
following:

* 0 - Debugging off
* 1 - Debugging on, but only for next request
* 2 - Debugging on for all future requests until turned off

**Example:** `php apex.php debug 1`



#### `server_type TYPE`

**Description:** Changes the server type of the installation.  The TYPE must be one of the following:  `all,
web, app, dbm, dbs, msg`.

**Example:** `php apex.php server_type web`


#### `update_masterdb`

**Description:**Allows you to update connection information for the master mySQL database. Useful if the
database information has changed, as upon that happening, you will no longer be able to access the software
and this information is stored within redis versus a plain text file.

**Example:** `php apex.php update_masterdb`


#### `clear_dbslaves`

**Description:** Clears all slave database servers from redis, and begins only connecting to the master mySQL
database.  This is useful if connection information on one or more slaves has changed without first being
updated in the software, as it will result in various connection errors by the software.  Please note, upon
clearing the slave database servers, you must enter the necessary slaves again via the Settings->General menu
of the administration panel.

**Example:** `php apex.php clear_dbslaves`


#### `update_rabbitmq`

**Description:** Allows you to update the connection information for RabbitMQ.  Useful if your connection
information has changed, as it may result in the software throwing errors, and the RabbitMQ connection
information is stored within redis versus a plain text file.


#### `compile_core`

**Description:** Should never be needed, and compiles the core Apex framework for the Github repository.
Places the Github repo of Apex within the system /tmp/ directory.

**Example:** `php apex.php compile_core`




