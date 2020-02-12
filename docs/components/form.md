
# HTML Form Component


&nbsp; | &nbsp;
------------- |------------
**Description** | Quality, customizable, flexible HTML forms, easy to implement Javascript validation, can be displayed blank, with values from the database, or values from previously POSTed data in case of user submission error, plus more.
**Create Command:** | ./apex create form PACKAGE:ALIAS
**Namespace:** | `apex\PACKAGE\form`
**File Location:** | /src/PACKAGE/form/ALIAS.php
**HTML Tag:** | `<e:function alias="display_form" form="PACKAGE:ALIAS">`


## Properties

This class only contains the one `$allow_post_values` property, which is a 1 or 0 and defines whether 
or not to use POSTed values as form values.  This is useful if for example, you may need to return the 
user to the form due to submission errors, as the form will still be filled in with their previously submitted values.


## Methods

Below describes all methods available within this class.


### `array get_fields(array $data)`

Returns an array of arrays defining the fields within this HTML form.  The `$data` 
array passed to this method is simply the attributes of the `<e:function>` tag that was used to call this form.

For example:
~~~php

public function get_fields() { 

$form_fields = array)
    'sep_contact' => array('field' => 'seperator', 'label' => 'Contact Details'), 
    'full_name' => array('field' => 'textbox', 'required' => 1, 'minlength' => 4), 
    'email' => array('field' => 'textbox', 'required' => 1, 'datatype' => 'email'), 
    'status' => array('field' => 'select', 'required' => 1, 'data_source' => 'hash:users:status'), 
    'awaiting_call' => array('field' => 'boolean', 'value' => 0), 
    'submit' => array('field' => 'submit', 'value' => 'add', 'label' => 'Add Contact')
);

// Return
return $form_fields;

}
~~~

The above will output a simple contact form that contains a couple text boxes, one which requires an e-mail address via Javascript validation, plus a select list to select from the "users:status" hash, a yes/no boolean, and a submit button.

Within the ~$form_fields` array, the key is always the name of the form field, and the value is an array specifying the type and attributes of the form field.  Below shows all variables available within that values array:

Key | Required | Value
------------- |------------- |-------------
field | Yes | The type of form field.  The following types are supported: *seperator, textbox, textarea, select, radio, checkbox, boolean, date, date_interval, amount, phone, custom, submit, button*
value | No | The default value of the form field.  Only used if another value does not take precedent, such as a record from the database or previously submitted POST data.
label | No | >The optional label of the form field.  This is used within the left-hand side of the form table, and is what the user sees.  If omitted, the uppercase version of the field name is used (eg. first_name = First name).
data_source | No | Only required for "select", "radio" and "checkbox" fields.  Defines the data source for the options.  See below for details on this value.
class | No | The CSS class name of the form field.  Defaults to "form-control".
id | No | The "id" attribute of the form field.  If not defined, defaults to "input_NAME".  Used to help identify the form field via Javascript if and when needed.
width | No | If defined, places the width within the <span class="code">style="width: VALUE;"</span> attribute of the form field (eg. 80px, etc.).


##### Javascript Validation Variables

The following variables can also be used within any of the form fields, and will add Javascript validation to the form as specified, without writing any Javascript yourself.

Key | Value
------------- |------------- 
required | Boolean (1/0), and defines whether or not the field is required.
datatype | The type of data that must be entered into the textbox.  Can be:<br /><ul><li>email</li><li>number</li><li>integer</li><li>decimal</li><li>alphanum</li><li>url</li></ul>
minlength | Minimum length of the textbox value.
maxlength | Maximum length of the textbox value.
range | Number range the value must be within (eg. 5 - 20)
equalto | Value must be equal to the value of the ID# of this textbox specified.  If you did not directly specify an "id" attribute for the textbox, just use "#input_NAME".  For example, if you have a confirm password field and you want it equal to the "password" textbox, you would use: equal = "#input_password".


##### textbox / textarea

The below table shows additional properties that are only available to the 'textbox' and 'textarea' fields.

Key | Value
------------- |------------- 
type | Can be either "text", "password" or "file".  Defaults to "text".
placeholder | The default placeholder of the textbox.
onfocus | The value of the `onfocus="..."` statement if it's being used.
onblur | The value of the `onblur="..."` statement if it's being used.
onkeyup | The value of the `onkeyup="..."` statement if it's being used.


##### `data_source` Attribute

The form fields "select", "radio" and "checkbox" also require a `data_source` property, which defines 
where to pull the list from.  This `data_source` attribute can be any of the below values.

Value | Description
------------- |-------------
`hash:PACKAGE:ALIAS` | The lists defined within the `$this->hash` array of the [package.php __construct() Function](packages_construct.md).  For example, using the data_source of `hash:users:status` will use all items within the `$this->hash['status']` array of the /etc/users/package.php file.
`table:TABLE_NAME:ORDER_BY:DISPLAY_VALUE:ID_COLUMN` | This pulls all rows from the database table `TABLE_NAME`, ordered by `ORDER_BY`.  The `DISPLAY_VALUE` is what is displayed to the user in the web browser, and should contain merge fields consistent with the table columns (eg. `~name~ (~email~)`), which will then be replaced by the values of each row.  Last, the `ID_COLUMN` is optional, and defaults to "id", but if specified the value of this column will be used as the value of the form field.  For example, `table:users_groups:name:~name~` will display all rows within the 'users_groups' table, ordered by the 'name' column.
`stdlist:LIST_TYPE` | Pulls from a standard / system list.  The `LIST_TYPE` can be either "language", "timezone", "country" or "currency".


### `array get_record(string $record_id)`

**Description:** Used to retrieve a record from the database or other source, to populate the form fields with values.  If 
the `<e:function>` that calls the form contains a `record="..."` attribute, this method is automatically called, and the values returned are used to 
populate the form fields.

For example, if the .tpl template file contains the tag:

~~~
<e:function alias="display_form" alias="myblog:post" record_id="~post_id~">
```

Then the associated .php file for the template contains:

```php
template::assign('post_id', registry::get('post_id'));
~~~

The `~post_id~` merge field within the .tpl file will be replaced with whatever "post_id" is in the query string, and the 
resultig form field will be populated with that post ID's values.


### `validate(array $data)`

**Description:** This provides additional server-side form validation.  Through the `forms::validate_form()` call, the form 
values are automatically validated against the validation properties included in the `get_fields()` method, plus this method 
is also called to provide any additional validation.  For example, if creating a new user, this will ensure the username does not already 
exist.  Simply give off `template_add_message($message, 'error')` calls for any validation errors that occur.





   


