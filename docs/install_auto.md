
# Auto Installation via YAML

Apex allows for automated installation via a YAML file.  Instead of the interactive CLI based installation 
wizard. it will read all information needed from a install.yml file within the installation directory, and skip the 
wizard altogether.  This is useful when deploying via docker-compose, as everything is 100% installed including all necessary packages 
and themes right away.


## YAML File Format

The below table lists the structure of the YAML file.  The file must be placed within the 
installation directory, named either install.yml or install.yaml.  When you first run the "apex" script, 
instead of getting the installation wizard, Apex will be automatically installed using the information within this file.

Key | Required | default | Description
------------- |------------- |------------- |------------- 
server_type | no | all | The type of server.  Can be: **all, web, app, dbs, dbm**
domain_name | Yes | - | The domain name being installed on.
enable_admin | No | 1 | A 10 or 0, defining whether or not to enable the administration panel.
enable_javascript | No | 1 | A 1 or 0 defining whether or not to enable Javascript.
db | Yes | - | Array of SQL database information.  See the below table for details.
redis | No | localhost:6379 | Array containing the redis information, any / all variables are optional.  Defaults to localhost:6379 with no password and db index of 0.  Available variables within this array are: **host, port, password, dbindex**
repos | No | - | List of arrays of any additional repositories to add.  The key is the hostname of the repo, with optional **ser** and **password** variables underneath it.  See below for an example.
packages | No | - | Simple array of package aliases to install.
themes | No | - | Simple array of theme aliases to install.
config | No | - | Associative array of any configuration variables to set, keys being the configuration variable itself, and value being the value to set.


#### db Array

The YAML file requires an associate array named **db**, which has the following variables available:

Key | Required | Default | Notes
------------- |------------- |------------- |------------- 
driver | No | mysql | The database driver, can be either "mysql" or "postgresql".  Defaults to "mysql".
autogen | No | 0 | A 1 or 0, defining whether or not to automatically generate the SQL database and user (root password required).  Defaults to 0.
dbname | Yes | - | The name of the SQL database.
user | Yes | - | The database username.
password | No | - | The database password.
host | No | localhost | The database host.
port | No | 3306 | The database port
readonly_user | No | - | If using a read-only database user, the username of said database user.
readonly_password | No | - | If using a read-only database user, the password of said database user.
root_password | No | - | The optional root databsae password, only required if the **autogen** variable is set to 1 or optionally if using a read-only database user and you want the installed to automatically set the necessary privileges.


## Sample YAML File

Below shows a sample YAML file.

~~~php
domain_name: myhostname.com

redis:
  password: my_redis_password
  dbindex: 3

db:
  driver: mysql
  dbname: apex
  dbuser: myuser
  password: my_db_password

repos:
  somehost.com:
    user: myuser
    password: my_repo_password

packages:
  - webapp
  - users
  - transaction
  - support
  - devkit
  - some_custom_package

themes:
  - canohost

config:
  "core:site_twitter": "https://twitter.com/client_username"
  "core:site_facebook": "https://facebook.com/client_user"

~~~



## Minimal YAML Example File

The below shows a bare minimum a YAML file may consist of:

~~~
domain_name:  mydomain.com

db:
  dbname: apex
  db:user myuser
  dbpass:  database_password

~~~


