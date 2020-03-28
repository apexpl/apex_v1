
# Generate Models

You may automatically generate the interface and PHP class for a model, using the columns of an existing database 
table via the `gen_model` CLI command.  You simply place a model.yml file within the installation directory with the 
necessary information, run the CLI command, and the two necessary PHP libraries will be automatically created.  Once you have the model.yml file in place, 
you simply run the CLI command:

`./apex gen_model`


## model.yml File Structure

You must place a model.yml file within the Apex installation directory (see below for example), which 
contains the following elements:

Variable | Type | Notes
------------- |------------- |------------- 
package | string | The alias of the package to create the model PHP files under.
alias | string | The alias of the new model.
dbtable | string | The name of the database table to create the model from.
exclude | array | Optional, and list any table columns you would like to exclude from the PHP code of the model.


That's it.  Once you have your model in place, via terminal run the command:

`./apex gen_model`

This will go through all columns within the database table, and generate both, an interface and PHP class with the 
necessary getter / setter methods.  The files will be placed at:

- /src/PACKAGE/interfaces/ALIASInterface.php
* /src/PACKAGE/models/ALIAS.php


## Example YAML File

Below shows an example model.yml file:

~~~
package:  mypackage
alias:  some_model
dbtable: package_some_model
excludes:
  - date_created
  - date_updated

~~~



