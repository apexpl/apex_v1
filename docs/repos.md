
# Repositories

If desired, you may easily setup your own repository where you can host your own private packages, copy
existing public packages over to develop them on your own, or anything else you may need.  To create a
repository, first ensure the Development Toolkit package is installed on your server.  Within terminal change
to the installation directory, and type:

`./apex install devkit`

Once installed, visit the Devel Kit->Repositories menu of the administration panel, and you will see one
repository listed.  Manage that repository, and change the settings as desired such as the name of the
repository, whether it's publicly viewable, etc.  Through this Repositories menu you may setup multiple
repositories on the same system.  This is done for organizational purposes, and you simply need to have
multiple hostnames pointing to the system, and can setup one repository on each hostname.


### Adding Repositories

If you have access to other repositories, you may easily configure them on any individual system.  In
terminal, type:

`./apex add_repo HOSTNAME USER PASS`

Obviously, change the hostname, username and password above as needed.  Once added, upon searching for or
installing packages, the system will also begin contacting the new repository as well.  The same applies if
using your own repository to publish and manage packages that you wish to distribute to clients.  Simply give
clients the hostname to your repository, they can add it as above, then install any packages available on your
repository with the standard command:

`apex.php install PACKAGE`



