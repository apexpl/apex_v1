
# Docker Installation

## Quick Setup via Auto Install

For a quick setup you may use the auto installation features of Apex to get up 
and running with an installation including various base packages within a few minutes.  To do so, within terminal run the following commands:

~~~
composer create-project apex/apex apex
cd apex
mv install_example.yml install.yml

sudo docker-compose up -d
sudo docker-compose exec apex apex
~~~

That's it, and your new system will be online with various base packages, and you 
can access your admin panel at http://127.0.0.1/admin/


## Standard Docker Setup 

Apex comes with a docker-compose.yml file, making installation via a docker container easy.  To start, download Apex with:

~~~
composer create-project apex/apex apex
cd apex
~~~

Open the `docker-compose.yml` file, and modify starting at line #45 with your 
desired mySQL login credentials.  Next, login to the shell and start the installation wizard with:

~~~
sudo docker-compose up -d
sudo docker-compose exec apex bash
apex
~~~

Few notes regarding the installation wizard:

* If not specified below, leave the field to its default by pressing enter.
* for domain name, enter "127.0.0.1"
* For the redis host, enter "redis"
* For the mySQL host, enter "mysql".
* For mySQL database, you can either choose to auto-generate the database / users, or define the credentials you set within the docker-compose.yml file previously.

That's it!  Apex should now be installed, and you can view the administration panel at http://127.0.0.1/admin/.  You will most 
likely want to install some additional packages, such as for example with:

`apex install webapp cms maintenance multi_admins users transaction support devkit`

You may also list all packages available to you with `apex list_packages`, and search all 
packages with `apex search TERM`.





