
# Installation

Installation of Apex is quite simple, and should only take a few minutes.  We suggest Ubuntu 18.04, but any
LINUX distribution will work.  Ensure you're running PHP 7.2 or later, then ensure your server meets the
requirements with the following command via SSH.

~~~
sudo apt-get update
sudo apt-get install redis rabbitmq-server libfreetype6-dev php php-mbstring php-json php-curl php-zip php-mysqli php-tokenizer php-redis php-bcmath php-gd php-gmp composer mysql-server
~~~

Once done, install Apex by following the below steps:

1. Install Apex as your document root with: `composer create-project apex/apex DIRNAME`
2. Modify Niginx / Apache configuration so the /public/ sub-directory is the document root of the server.
3. Change to the installation directory, and start the installation wizard by typing `php apex.php`.  The wizard should be quite straight forward, and if unsure, just leave the server type to the default of "all".
4. Follow any instructions the installation wizard provides, such as creating the crontab job, etc.
5. Done!  Enjoy Apex.


#### URL Rewriting

If using Apache, ensure the `AllowOverride All` directive within your virtual host is set, and the
*/public/.htaccess* file should
handle the rewrites correctly.  If using Nginx, you will need to slightly modify the server confirmation.  Within the `location { }` directive for
document root, add the line:

`try_files $uri $uri/ /index.php?$args;`


### The `apex` phar archive

You will notice a */apex* script within the installation directory.  Although not required, if developing with
Apex it is recommended you move this file to your environment path such as /usr/bin/.  By doing so, whenever
the documentation calls for you to type `php apex.php`, you can replace it with simply `apex`.

For example, instead of typing `php apex.php install users`, you will instead be able to simply type `apex
install users` and get the same result.


### Installing Packages

Once the base installation is done, you can easily install additional packages.  You may list all packages
available with:  `php apex.php list_packages`

You can install a package with, for example for the "users" package:  `php apex.php install users`

You can also install multiple packages at one time with for example:  `php apex.php install webapp users
transaction support`


### Private Packages

You may also have access to private packages that were either purchased commercially or developed exclusively
for you.  In this case, you should have a hostname, username and password to a private repository.  You must
first add the repository to your system with:

`php apex.php add_repo HOST USERNAME PASSWORD`

Once done, you can go ahead and install your private packages as normal with:  `php apex.php install PACKAGE`



