
# Package Structure

This page will explain the basic structure of packages, including how to develop and publish them.  You can
easily develop packages that consist of any functionality you desire, then have the freedom to instantly
install, remove and upgrade those packages on systems throughout the internet, and have them simply work
right out of the box.  You can list and sell packages on our marketplace, open source them by publishing them
to our public repository, or even start your own private repository and keep them private for your commercial
clients.


### Create Package

You can easily create a new package any time.  Within terminal change to the Apex installation directory, and
type:

`php apex.php create_package PACKAGE_ALIAS`

Where `PACKAGE_ALIAS` is the alias of the new package.  That's it, your new package is now created and ready
for development.

### Basic Structure

Each package contains a few sub-directories as described in the below table.

Directory | Description 
------------- |------------- 
/etc/PACKAGE_ALIAS | Configuration of the package, including install / remove SQL code. 
/src/PACKAGE_ALIAS | PHP code of the package, including all libraries and components. 
/tests/PACKAGE_ALIAS | Unit tests for the package.


#### /etc/PACKAGE_ALIAS Directory

This directory holds configuration and installation details on each package.  Below lists all files within
this directory:

File | Description 
------------- |------------- 
components.json | Will only exist once the package has been published, and is a JSON file containing information on all components included within the package.
package.php | The main package configuration file, and defines things such as configuration variables, hashes, and menus that are included in the package.  Full details on this package are found in a below section on this page. 
install.sql | Optional, and if exists, all SQL code included within this file will be executed against the database upon installation. 
install_after.sql | Optional, and if exists, all SQL within this file will be executed against the database at the very end of package installation, meaning after all components have been installed. 
reset.sql | Optional, and if exists, all SQL code within will be executed when the package is reset from within the administration panel.  This is meant to clear all database from the package, and reset it to just after it was installed. 
remove.sql | Optional, and if exists, all SQL code within this file will be executed against the database upon removal of the package.  Should drop all database tables created during installation. 
/upgrades/ | Directory that ontains details on all upgrade points created against this package.


#### package.php Configuration File

This is the main configuration file for the package, and defines various basic properties such as name and
access level, configuration variables, hashes, menus to add, and more. Due to the size of this file, a
separate page has been devoted to it, and for full details please visit the [Package
Configuration](packages_config.md) page.


### Develop the Package

Now that the package has been created, go ahead and develop it by adding all the desired libraries, views,
components and unit tests.  For a quick example of how to add a library and view to the package "casino", at
terminal you could type:

~~~
php apex.php create lib casino:games

php apex.php create view admin/casino/bets casino
~~~

The above commands would add the components, and create the necessary blank files for you to develop.  For
details on all supported components, and how to create / delete them, please visit the following links:


* [CLI Commands - Create / Delete Components](cli.md#component)
* [List of Components](packages.md#components)


### Publish Package

Once development is complete, it is very easy to publish your package to a repository.  If you do not wish to
publish to the main public Apex repository, manage the package via the Devel Kit-&gt;Packages menu of the
administration panel, and change the repository assigned to the package.  If you wish to upload to your own
private repository, please see the [Repositories](repos) page of this documentation.

To publish your package, in terminal change to the installation directory, and type:

`php apex.php publish PACKAGE_ALIAS`

That's it.  The package will then be published to the repository, and can then be installed on any Apex system
by typing:

`php apex.php install PACKAGE_ALIAS`




