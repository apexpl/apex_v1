
# Installation

Installation of Apex is quite simple, and should only take a few minutes.  We suggest Ubuntu 18.04, but any
LINUX distribution will work.  Ensure you're running PHP 7.2 or later, then ensure your server meets the
requirements with the following command via SSH.

~~~
sudo apt-get update
sudo apt-get install redis rabbitmq-server libfreetype6-dev php php-mbstring php-json php-curl php-zip php-mysqli php-tokenizer php-redis php-bcmath php-gd php-gmp composer git
~~~

Once done, install Apex by running the following commands:
~~~
composer create-project/apex/apex DIRNAME
cd DIRNAME
./apex
~~~

This will create a directory at `DIRNAME`, download Apex and all dependencies into it, and start the installation wizard.  Follow the instructions 
within the installation wizard to complete the installation.  You must also change the document root of 
your HTTP server in Apache / Nginx to the /public/ sub-directory of the Apex installation.  Once done, Apex should begin coming up fine in your web browser.  Enjoy!


### URL Rewriting

If using Apache, ensure the `AllowOverride All` directive within your virtual host is set, and the
*/public/.htaccess* file should
handle the rewrites correctly.  If using Nginx, you will need to slightly modify the server confirmation.  Within the `location { }` directive for
document root, add the line:

`try_files $uri $uri/ /index.php?$args;`


### Installing Packages

Once the base installation is done, you can easily install additional packages.  You may list all packages
available with:  `./apex list_packages`

You can install a package with, for example for the "users" package:  `./apex install users`

You can also install multiple packages at one time with for example:  `./apex install webapp users
transaction support`


### Private Packages

You may also have access to private packages that were either purchased commercially or developed exclusively
for you.  In this case, you should have a hostname, username and password to a private repository.  You must
first add the repository to your system with:

`./apex add_repo HOST USERNAME PASSWORD`

Once done, you can go ahead and install your private packages as normal with:  `./apex install PACKAGE`



