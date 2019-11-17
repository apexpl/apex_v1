
# Package Manager

Again, this aspect of your operation will most likely be handled by your technical team, but nonetheless, this
page gives a brief overview of packages and repositories, and how they work within Apex.


### Repositories

Through the Maintenance->Package Manager menu you will see a Repositories tab where you can view and manage
all repositories configured on this system.  Repositories are where developers upload their packages and
themes, which can then be downloaded and installed on your system.  If you purchased a commercial software
product, you will most likely have access to a private repository.  The seller should provide you with details
to the private repository, which you can then enter in this tab.  Once added, Apex will always query the
private repository when looking for available packages, themes, and updates.


###Installing Packages

Through the Maintenace->Package Manager you will see a menu listing all packages available to your system.
When you visit this menu, Apex will query all repositories configured on your system, obtain a list of all
available packages that are not installed on your system, and display them here.  If you ever want to install
an available package, simply login to your server via SSH, change to the installation directory, and type:

`./apex install PACKAGE_ALIAS`

This will download the package from the necessary repository, and install it on your server.


### Installing Upgrades

You can view a list of all available upgrades via the first tab of the Maintenance->Package Manager menu.  If
and when you wish to install the availalbe upgrades, simply login to your server via SSH, change to the
installation directory, and type:

`./apex upgrade`

This will download all available upgrades from the necessary repositories, and install them on your server.
Please note, it is highly recommended you keep your system up to date, as all depending on development
progresses on the various packages, if you wait too long and try to install too many upgrades at once, it may
cause errors and break the system.


