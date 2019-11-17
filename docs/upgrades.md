
# Upgrades and Version Control

Apex offers hands free version control, as it will automatically keep track of all modifications made to your
packages.  Upgrades can be instantly published to their repository, from where they can be instantly installed
on any systems with that package installed.  Full rollback functionality is also supported, so in case an
upgrade goes wrong, the individual system can be rolled back to its previous state before the upgrade.


### Create Upgrade Point

After you have initially published your package, you will want to create an upgrade point on it.  Within
terminal, type:

`./apex create_upgrade PACKAGE`

This will create a new upgrade point on the package, plus scan all files within the package, and save their
SHA1 hash.  These hashes are used later when publishing the package to determine which files were modified.


### Upgrade Structure

After creating an upgrade point, a new directory will be created at /etc/PACKAGE/upgrades/VERSION.  This
directory contains a few pertinent files, which are explained below.

File | Description 
------------- |------------- 
upgrade.php | Allows you to define PHP code to be executed upon installation  / rollback of the upgrade. 
install.sql | Any SQL code within this file is executed against the database upon installation of the upgrade. 
rollback.sql | Any SQL code within this file is executed against the database if / when the upgrade is rolled back.


### Publish Upgrade

Once you have completed development and testing of the upgrade, you will want to publish it to the repository,
making it available to all systems that have the package installed. To publish an upgrade, within terminal
type:

`./apex publish_upgrade PACKAGE`

It will compile the upgrade as necessary, and publish it to the repository where the package resides.  The
upgrade is now instantly available to all Apex systems with the package installed.


### Installing Upgrades

Installing upgrades couldn't be easier.  If desired, you can always view a list of installed packages that
have new upgrades available via the Maintenance->Package Manager menu of the administration panel.  To
install available upgrades, simply open up terminal and type:

`./apex upgrade`

It will simply download the appropriate upgrades from their respective repositories, and install them on the
system.


### Rollback Upgrade

If ever needed, upgrades can be easily rolled back on an individual system.  Within terminal, simply type:

`./apex rollback PACKAGE`

You will be prompted to select which version you would like to rollback to.  The system will then revert to
the exact same codebase as before the upgrade was installed.




