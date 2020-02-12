
# Contributing

Contributions to Apex are greatly appreciated and strongly encouraged.  Help thousands of entrepreneurs around the 
world establish professional and robust online operations by contributing to Apex.


## New Packages

One of the best ways to contribute is by developing new packages providing any functionality you 
wish.  The main Apex repository is available to the public, and you may publish any packages you desire.  You may 
choose either to open source and offer your packages for free to the public, or mark them as commercial requiring a fee to 
install / download of which you receive the majority share of.  For full information including links to developer training 
documentation, please visit:
    https://apex-platform.org/developers


## Core

Contributions to the core Apex platform are greatly appreciated, will be listed on the Contributors page of the Apex 
web site, and are just a standard Github repository.  Please feel free to fork, check out the issues, and contribute via Github at:
    https://github.com/apexpl/apex/


## Existing Packages

Many of the base packages such as user management, transaction and payments system, support ticketing, and others are developed and managed by Apex.  All contributions to 
these packages are also greatly appreciated, and you may view a list of all Apex managed packages at:
    https://github.com/apexpl/

You can either contribute the standard way of a fork with pull request, or if preferred for development and testing purposes, 
by using the version control built-in to Apex by:

1. Fork desired Gitbub repository.
2. Install Apex on your devel environment, including the package you wish to contribute to.
3. Open the /etc/PACKAGE/package.php file, within the properties replace the `$git_upstream_url` with the URL to the master Apex repository, and set the `$git_repo_url` property to your forked Gitbub repository.
4. Create an upgrade point.  Within terminal run:  `./apex create_upgrade PACKAGE`
5. Complete any desired modifications, without worry of keeping track of files / lines modified.
6. When ready, publish the upgrade via terminal with: `./apex publish_upgrade PACKAGE`
7/ Follow the instructions, which will ask you to run the shell script at **/src/PACKAGE/git/git.sh**.  This will commit and push your modifications to your Github repository.
8. Open a pull request on the master Apex repository as normal.


## Support

I'm available 18 hours per-day, and obviously always more than happy to help in any way I can.  Please don't hesitate to contact 
me directly via e-mail at matt.dizak@gmail.com, or go ahead and post any questions / issues you have on the Apex sub-reddit at:
    https://reddit.com/r/Apex_Platform

Thank you for reading this, very much looking forward to hearing from you, and seeing your contributions to Apex.  Let's make the software world a better place for entrepreneurs.


