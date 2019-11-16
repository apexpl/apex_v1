
# Github Integration

Apex offers integration with Github, allowing you to take advantage of the collaboration and project 
management functionality of Github while developing and maintaining Apex based packages.


## Initialize Package

To create a new Apex package that will be used in conjunction with Github, complete the following steps:

1. Create the Apex package in terminal as normal: `./apex create_package mypackage`
2. Create a blank Github repository, and copy down the URL (eg. https://github.com/myuser/mypackage.git).
3. Modify the package configuration file at **/etc/mypackage/package.php**, and within the properties modify the `$github_repo_url` variable accordingly.
4. Develop the Apex package as normal.
5. Upon completion of initial development, publish the package to the Apex repository as normal: `./apex publish mypackage`
6. Initialize the Github repository with: `./apex git_init mypackage`
7. Change to the newly created **/src/mypackage/git/** directory, make any desired changes to the commit.txt file, then run the git.sh script with: `./git.sh`.  This will initialize the Github repository, add all files, plus do a commit and push.  You will be prompted for your Github username / password, and the package will be uploaded to the Github repository.

That's it!  The package has now been published to the Apex repository, and can be instantly installed on any APex installation.  Plus the source 
code is on Github, allowing for standard collaboration and project management.  Upon installing the package into an Apex installation, the source code will now be downloaded 
directly from the Github repository instead of the Apex repository. 


## Publish Upgrades

The Github integration still retains hands free version control within Apex packages.  Upon initially publishing the package, to release an upgrade 
complete the following steps:

1. Create an upgrade point in Apex with: `./apex create_upgrade mypackage`
2. Develop the upgrade as you see fit, either within the Apex installation, by merging pull requests within Github, etc.
3. When ready, ensure the **/etc/mypackage/** directory is properly updated if any changes were made to the package.php configuration file, or SQL files.
4. Publish the upgrade as normal with: `./apex publish_upgrade mypackage`
5. If the modifications were made within Github (ie. merging pull requests), then nothing needs to be done, and upgrade released!  Otherwise, if modifications were made on your local Apex installation, change to the **/src/mypackage/git/** directory, and run the `./git.sh` script to commit and push the changes to the Github repository.

That's it!  When you publish an upgrade, the Apex repository will retrieve the contents of the Github repository, scan the files for modifications compared to the 
previous version, and include any modified files within the upgrade.  


## Contribute

To contribute to an existing Apex package, ensure you have Apex installed locally, and complete the following steps:

1. Install the desired package with: `./apex install mypackage`
2. Fork the existing Github repo into your Github account.  If you are unsure of the repository URL, look within the properties of the **/etc/mypackage/package.php** file.
3. Modify the **/etc/mypackage.php** file, and change the Github properties.  Set the `$github_upstream_url` to the main repository URL, the `$github_repo_url` to your Github fork repository URL, and the `$github_branch_name` to whatever you wish to call your branch.
4. Create an upgrade point on the package with: `./apex create_upgrade mypackage`
5. Complete any desired modifications.
6. Publish the upgrade to the Apex repository with:  `./apex publish_upgrade mypackage`
7. Change to the **/src/mypackage/git/** directory, and execute the `./git.sh` script to commit and push the changes to your forked repository.
9. Visit the main Github repository, and create a pull request with your forked repository to be merged into the master branch.

That's it!  When you publish the upgrade, the Apex repository will notice an upstream URL is defined, hence will treat it as a pull request within GIthub that still has to be merged into 
the master branch, and will not actually commit the changes to the Apex repository.  Instead, it will simply generate the git.sh script that includes all modified files.  You can go ahead and continue publishing upgrades 
as desired for each commit you wish to make to the Github repository.


## Sync from Github Repository

At times you may wish to sync your local Apex installation of a package with the current codebase of the Github 
repository.  For example, if several pull requests were merged into the master branch, you may want to upgrade your local installation to the latest 
codebase.  This can be done with the `git_sync` CLI command, such as:

`./apex git_sync mypackage`

This will download the package contents from the Github repository, scan all files, and update the codebase on your local Apex installation to the latest code.





