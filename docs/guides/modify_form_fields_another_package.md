
# Adding Fields to a HTML Form Owned by Another Package

At times while developing a package you will want to add / remove fields within an existing HTML form component 
that is owned by another package.  This guide serves to show you exactly how to do that within Apex.

## Create Worker

Every time a HTML form component is displayed, the "core.forms.get_fields" event message is dispatched, allowing us to create a worker / listener for this 
event message, and execute code within our package every time a HTML form is displayed.  To start, create the necessary work, and for example 
if your package name is "mypackage" you would use something like:

`./apex create worker mypackage:form_fields core.forms`

This will create a new file at */src/mypackage/worker/form_fields.php*, and you must define a `get_fields()` method 
within it.  To start, you need to ensure it is only run when the HTML form component you're modifying is displayed.  For example, if 
we wish to modify the user registration form, we want to ensure it's the "users:register" form, so:

~~~php

public function get_fields(event $msg)
{

    // Get message params
    list($form, $form_alias, $data) = $msg->get_params();
    if ($form_alias != 'users:register') { return; }

}
~~~

That's it!  As parameters, this message will include the form object itself, the form alias being displayed, and the additional data array passed within the HTML tag within 
the view.  We simply check to ensure it's the "users:register" form that is being displayed, and return null otherwise.


## The `apex\app\web\form_fields` Class

Now that we're sure our method will only run for the HTML form component we want to modify, we need to use the `apex\app\web\form_fields` class 
to modify the fields within it.  Within our method, we want to add code such as:

    ~~~php
    // Initialize
    $client = app::make(\apex\app\web\form_fields::class);

    // Add form fields
    $client->add('sep_fees', array('field' => 'seperator', 'label' => 'Fees'));
    $client->add('recurring_fee', array('field' => 'amount'));
    $client->add('recurring_interval', array('field' => 'date_interval'));

    // Remove field
    $client->remove('some_field');
~~~


The above will create three fields, one seperator, one amount field, and a date interval field.  It will also remove the form field named "some_field".  The `add()` method 
takes the name of the form field to add, and ar array comprising of the attributes of the field, exact same as when defining the HTML form component itself.










