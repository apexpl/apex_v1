
# `apex` CLI Commands

Within the installation directory Apex comes with both, an apex.php script and an `apex` phar archive.  Both are
the exact same, but you may move the `apex` phar archive within your environment path (ie. /usr/bin/) allowing
you to simply type "apex" instead of "./apex" to perform CLI commands.

Various CLI commands are built into Apex which help facilitate development and package / upgrade / theme
management.  You can view a list of all availble commands by typing "apex.php help" within terminal, and below
explains all available commands.

1. <a href="#general">General (package / upgrade / theme management)</a>
2. <a href="#package">Package / Upgrade Development</a>
3. <a href="#component">Component Development</a>
4. <a href="#system">System Maintenance</a>
5. <a href="#github_integration">git / Github Integration</a>


<a name="general"></a>
## General Commands

Various general CLI commands are available allowing you to search all repositories for packages, install and
upgrade packages / themes, plus more.  Below describes all general CLI commands available.

#### `list_packages`

**Description:** Lists all packages available to the system from all repositories configured on the system.

**Usage:** `./apex list_packages`


#### `search TERM`

**Description** Searches all repositories configured on the system for a specific search term.

**Example:** `./apex search mailing list`


#### `install PACKAGE1 PACKAGE2...`

**Description:** Downloads a package from a repository, and installs it on the system. This command allows you
to specify multiple packages at one time as well.

**Example:** `./apex install webapp users transaction support`


#### `upgrade [PACKAGE]`

**Description:** Checks the repositories, and automatically downloads and installs any available upgrades.  if
desired, you may optionally specify a single package to upgrade. Otherwise, all installed packages will be
upgraded.  It's recommended to run this command about once a week to ensure your packages are always up to
date.

**Example:** `./apex upgrade`


#### `check_upgrades [PACKAGE]`

**Description:** Checks the repositories for any available upgrades, and provides information on the latest
available version of each package.  This does not download or install the upgrades, and instead only provides
what upgrades are available.

**Example:** `php check_upgrades`


#### `list_themes`

**Description:** Lists all themes available from all repositories configured on this system.

**Example:** `./apex list_themes`


#### `install_theme THEME_ALIAS`

**Description:** Downloads the specified theme from the repository, and installs it on the system.

**Example:** `./apex install_theme mydesign`


#### `change_theme AREA THEME_ALIAS`

**Description:** Allows you to change the currently active theme on an area.  The `AREA` should be either
"public" or "members", and then the alias of the theme you would like to activate.  The theme must already be
installed on the system.

**Example:** `./apex change_theme public mydesign`



<a name="package"></a>
## Package / Upgrade Development

Various CLI commands are available allowing you to easily create, publish, and delete packages and upgrades
within repositories.  Below explains all CLI commands available for package / upgrade development.


#### `create_package PACKAGE [REPO_ID]`

**Description:** Creates a new package on the local system, which can then be developed, and later published
to a repository.  This creates the necessary directories within /etc/ and /src/, and upon creation, you can
begin creting components on the package via the apex.php script.

**Example:** `./apex create_package casino`


#### `scan PACKAGE`

**Description:** Scans the */etc/PACKAGE/package.php* configuration file, and updates the database as needed.
Use this during development, after you have updated the package.php file with new information such as config
variables or menus, run this to reflect the changes within the database and system.

**Example:** `./apex scan casino`


#### `publish PACKAGE`

**Description:** After developing a package, you may publish it to the repository using this command.  This
will compile the package, and upload it to the repository,.  Once done, you can then begin installing the
package on other systems via the `install PACKAGE` command.

**Example:** `./apex publish casino`


#### `delete_package PACKAGE`

**Description:** Completely removes a package and all of its components from the system. Please note, this
does not remove the package from any repositories it has been previously uploaded to, and only removes it from
the local system.

**Example:** `./apex delete_package casino`


#### `create_upgrade PACKAGE [VERSION]`

**Description:** Creates a new upgrade point on the specified package.  You may optionally define a version
for the upgrade, and if left unspecified, the system will simply increment the third element of the current
version by one.  This will basically create an "image" of the package by creating a SHA1 hash of every file,
and upon publishing the package, these hashes will be checked allowing the system to determine all
modifications made to the package since the upgrade point was created.  This allows for virtually hands free
version control.

**Example:** `./apex create_upgrade casino`


#### `publish_upgrade [PACKAGE]`

**Description:** Once you've completed developing an upgrade after creating the upgrade point, you can upload
it to the repository using this command.  Once uploaded, all systems with the package installed can install
the upgrade on their system with the `apex.php upgrade` command.

**Example:** `./apex publish_upgrade casino`


#### `create_theme THEME_ALIAS`

**Description:**  Creates a new theme, which can then later be published to a repository if desired.  This
creates the necessary directory structure within the /views/themes/ and /public/themes/ directories, from
which you can begin creating the theme.

**Example:** `./apex create_theme mydesign`


#### `init_theme THEME_ALIAS`

**Description:**  After initially creating the theme, and once you have the base header.tpl, footer.tpl and /tpl/index.tpl files in place, run this command to help 
initialize the theme and speed up integration.  This command will go through the .tpl files of the theme, and update the internal links to javascript / CSS / images 
as necessary with the ~theme_uri~ tag, helping speed up the integration process.

**Example:** `./apex init_theme mydesign`


#### `publish_theme THEME_ALIAS`

**Description:** Will publish the specified theme to the repository.  Once published, it can be installed on
any system with the `apex.php install_theme THEME_ALIAS` command.

**Example:** `./apex publish_theme mydesign`


#### `delete_theme THEME_ALIAS`

**Description:** Deletes the specified theme from the system, including all files and directories.  Please
note, this only deletes the theme from your local system, and not any repositories.

**Example:** `./apex delete_theme mydesign`



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

**Example (lib:** `./apex create lib casino:games`

**Example (view:** `./apex create view admin/games/bets casino`

**Example (worker):** `./apex create worker casino:user users.user`

**Example (controller):** `./apex create controller core:http_requests:games casino`


#### `delete TYPE PACKAGE:[PARENT:]ALIAS`

**Description:** Deletes a component from the system.  The two variables passed are the exact same as the
`create` command above.  This will permanently remove the component from the filesystem and database.

**Example (lib):** `./apex delete lib casino:games`

**Example (view):** `./apex delete view admin/casino/bets`

**Example (controller):** `./apex delete controller core:http_requests:games`


#### `scan PACKAGE`

**Description:** Scans the */etc/PACKAGE/packge.php* configuration file, and updates the database as needed.
Use this during development, after you have updated the package.php file with new information such as config
variables or menus, run this to reflect the changes within the database and system.

**Example:** `./apex scan casino`



<a name="system"></a>
## System Maintenance
    
There are several CLI commands available for general system maintenance, such as addinf / updating
repositories, updating connection information for both mySQL master database and RabbitMQ, and more.  Below
explains all system maintenance commands available.


#### `add_repo HOSTNAME [USERNAME] [PASSWORD]`

**Description:** Adds a new repository to the system, which is then checked for available packages, themes and
upgrades.  The HOSTNAME is the base hostname of the repository, and you may optionally specify a username and
password in case of a private repository, which will be provided to you by the owner of the repository.

**Example:** `./apex add_repo repo.somevendor.com myuser mypassword`


#### `update_repo HOSTNAME [USERNAME] [PASSWORD]`

**Description:** Allows you to update an existing repository already in the system with a username and
password.  Specify the hostname of the repo, and assuming it is already configured on the system, you will be
prompted for a username and password, which will be provided to you by the owner of the repository.  This is
to gain access to any private packages you may have purchased.

**Example:** `./apex update_repo repo.somevendor.com`


#### `mode [DEVEL|PROD] [DEBUG_LEVEL]`

**Description:** Changes the server mode between either development or products, and allows you to change the
debug level between 0 -5.

**Example:** `./apex mode devel 4`


#### `debug NUM`

**Description:** Specifies whether or not to save debugging information for the next requests, which can then
be viewed through the Dev Kit->Debugger menu of the administration panel.  The value passed must be one of the
following:

* 0 - Debugging off
* 1 - Debugging on, but only for next request
* 2 - Debugging on for all future requests until turned off

**Example:** `./apex debug 1`



#### `server_type TYPE`

**Description:** Changes the server type of the installation.  The TYPE must be one of the following:  `all,
web, app, dbm, dbs, msg`.

**Example:** `./apex server_type web`


#### `update_masterdb`

**Description:**Allows you to update connection information for the master mySQL database. Useful if the
database information has changed, as upon that happening, you will no longer be able to access the software
and this information is stored within redis versus a plain text file.

**Example:** `./apex update_masterdb`


#### `clear_dbslaves`

**Description:** Clears all slave database servers from redis, and begins only connecting to the master mySQL
database.  This is useful if connection information on one or more slaves has changed without first being
updated in the software, as it will result in various connection errors by the software.  Please note, upon
clearing the slave database servers, you must enter the necessary slaves again via the Settings->General menu
of the administration panel.

**Example:** `./apex clear_dbslaves`


#### `update_rabbitmq`

**Description:** Allows you to update the connection information for RabbitMQ.  Useful if your connection
information has changed, as it may result in the software throwing errors, and the RabbitMQ connection
information is stored within redis versus a plain text file.


#### `compile_core`

**Description:** Should never be needed, and compiles the core Apex framework for the Github repository.
Places the Github repo of Apex within the system /tmp/ directory.

**Example:** `./apex compile_core`


<a name="github_integration">
## git / Github Integration

Apex also offers full integration with Hithub or any hosted git service.  This allows you to take advantage of the collaboration and 
project management functionality of git while retraining the structual integrity of Apex packages.  Below explains all git commands supported.

**NOTE:** For full details, please visit the [Github Integration](github.md) page of the documentation.


#### `git_init PACKAGE`

**Description:**  Used to initialize a package and ready it for publishing to Github.  This will create a sub-directory at */src/package_alias/git/* if one doesn't already 
exist, then copy over all package files into it, plus create a git.sh file, readying it for a push to github.

**Example:** `./apex git_init mypackage`


#### `git_compare PACKAGE`

**Description:**  Used to compare the current filesystem to the Github repository, and create the necessary git.sh file ensuring the Github repo is 
running the latest version of the code base.  This command assumes the local filesystem is more up to date than the Github repository.

**Example:** `./apex git_compare PACKAGE`


#### `git_sync PACKAGE`

**Description:** Used to sync the contents of the Github repository with the local filesystem.  This assumes the Github repo is 
more up to date than the local filesystem, will download the repository tabball, and replace any updated files with those on the local filesystem.

**Example:** `./apex git_sync PACKAGE`

 


