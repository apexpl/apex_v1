
# Generate Models

You may automatically generate the interface and PHP class for a model, using the columns of an existing database 
table via the `gen_model` CLI command.  This command looks like:

**Usage:** `./apex gen_model PACKAGE:ALIAS DBTABLE`

Where:

- `PACKAGE` - The alias of any package installed on the system.
- `ALIAS` - Any desired alias for the model, case-sensitive.
- `DBTABLE` - The name of any table within the database.

Upon running the above CLI command, the software will generate a mode and interface for the new model, which will be located at:

- /src/PACKAGE/models/ALIAS.php
- /src/PACKAGE/interfaces/ALIASInterface.php

## Model Functionality

As you will see within the model PHP class, instead of separate get / set methods for each property, the 
magic `__call()` method is used instead.  This is done as it keeps the scope of properties at private, while allowing ease of flexibility as many properties are just 
simple get / set methods.  Any properties that require additional validation or functionality can be manually added in, and they will be executed instead.


