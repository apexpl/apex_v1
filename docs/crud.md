
# CRUD Scaffolding

Apex allows for the automated generation of necessary components and views to allow for all CRUD functionality.  This is done by creating a small crud.yml YAML 
file within the installation directory, then running the `./apex crud` CLI command.  Below explains the structure of the crud.yml file, and provides from examples.  Once the crud.yml file is 
in place, you can automatically create all necessary components within terminal by typing:

`./apex crud crud.yml`


## YAML File Structure

The below table lists all the level 1 / main level keys within the YAML file.  Please note, you must already have a SQL database table 
created within your database, and the columns from that will be used to create the necessary form and table.  Apex does take into account the type of database column to 
determine the form field to use and how to format the database column.

Key | Required | Note
------------- |------------- |------------- 
package | Yes | The alias of the package to create components for.
dbtable | Yes | The name of the database table.  The table must already exist.
alias | No | Optional alias to use for the components.  If omitted, the dbtable value will be used.
form | No | An array comprising of details on the HTML form component to be created.  See below for details of this array.
table | No | An array comprising of details on the data table component to be created.  See below for details of this array.
views | No | An array comprising of details on the views that will be created.  See below for details of this array.


#### `form` Array

The `form` array consists of the following keys.  All keys are optional.

Key | Note
------------- |------------- 
exclude | Comma delimited string of columns from within the database table to exclude as form fields.
`column_name` | An array named any column within the database for which you want to specify specific attributes.  The attributes you may define are the same as those listed within the [HTML Form](components/form.md) component page, such as: *field, value, data_source, width*.


#### `table` Array

The `table` array consists of the following keys.  All keys are optional.

Key | Note
-------------| ------------- 
exclude | Comma delimited string of columns from within the database table to exclude as table columns.
manage_button | A 1 or 0, definitning whether or not a 'Manage" column within button / link is included in the table.  Defaults to 0.
delete_button | A 1 or 0, defining whether or not a delete button is included at the bottom of the table allowing rows to be deleted via AJAX.  Defaults to 1.
rows_per_page | The number of rows to display per-page.  Defaults to 25.
form_field | The type of form field to use as the left most column, and can be either: *none, radio, checkbox*.  Defaults to *checkbox*.
has_search | A 1 or 0, defining whether or not an AJAX powered search textbox appears on the top-right of the table.  Defaults to 0.
search_columns | Only applicable if `has_search` is set to 1, and a comma delimited list of column names within the database table to search.


#### `views` Array

The `views` array consists of the following keys.  All keys are optional.

Key | Note
-------------| ------------- 
admin | Realative URI to the view within the administration panel (eg. admin/settings/my_object).  If defined, will create both this view and another view with "_manage" appeneded.
members | Realative URI to the view within the member's area, if applicable.



## Example YAML Files

This section contains some example YAML files that can be used for CRUD scaffolding.  Again, simply save these files as crud.yml within the 
installation directory, then from terminal run `./apex crud` to have the necessary components automatically generated.

The below example will create the following components, modelled after the columns within the 'mypackage_products' database table.

- HTML form with alias 'product' within the 'mypackage' package.
- Data table with the alias 'products' within the 'mypackage' package.
- Views at both, admin/settings/mypackage_products and admin/settings/mypackage_products_manage.

~~~
package: mypackage
dbtable: mypackage_products

form:
  exclude: is_active
  trial_period:
    field: date_interval

table:
  exclude: trial_fee,trial_period
  manage_button: 1

views:
  admin: admin/settings/mypackage_products

~~~

















