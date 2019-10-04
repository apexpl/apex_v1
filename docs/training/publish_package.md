
# Publish Package

Now that all development is complete, we're ready to publish our package to the repository.  We specified to save the 
package to our local repository.  To do this, ensure you first create a user via the Users->Create New User of the administration panel, then update the repository 
login information within terminal by typing:

`php apex.php update_repo 127.0.0.1`

Obviously, change the "127.0.0.1" to whatever the domain name of your machine is.  It will prompt you for a username and 
password, and just enter the user / pass of the user you just created.


### Publish Package

Now that we have login info defined for the repository, go ahead and publish the package.  Within terminal, type:

`php apex.php publish training`

This will publish the package to our local repository.  From here, we can easily install the package on any 
other system by simply adding the repository with:

`php apex.php add_repo 127.0.0.1`

Again, change the "127.0.0.1" to your machine's hostname.  From there, you can install with simply:

`php apex.php install training`


### Next

Now that our package is published and ready, last aspect to cover is [Upgrades](upgrades.md), and version control within Apex.


