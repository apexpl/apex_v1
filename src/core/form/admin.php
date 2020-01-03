<?php
declare(strict_types = 1);

namespace apex\core\form;

use apex\app;
use apex\libc\db;
use apex\libc\view;


class admin 
{




    public $allow_post_values = 1;

/**
 * Defines the form fields included within the HTML form. 
 *
 * @param array $data An array of all attributes specified within the e:function tag that called the form.
 *
 * @return array Keys of the array are the names of the form fields.
 */
public function get_fields(array $data = array()):array
{ 

    // Set form fields
    $form_fields = array(
        'sep1' => array('field' => 'seperator', 'label' => 'Login Credentials'),
        'username' => array('field' => 'textbox', 'label' => 'Username', 'required' => 1, 'datatype' => 'alphanum'),
        'password' => array('field' => 'textbox', 'type' => 'password', 'label' => 'Desired Password', 'required' => 1),
        'confirm-password' => array('field' => 'textbox', 'type' => 'password', 'label' => 'Confirm Password', 'placeholder' => 'Confirm Password', 'required' => 1, 'equalto' => '#input_password'),
        'full_name' => array('field' => 'textbox', 'label' => 'Full Name', 'placeholder' => 'Full Name', 'required' => 1),
        'email' => array('field' => 'textbox', 'label' => 'E-Mail Address', 'required' => 1, 'datatype' => 'email'),
        'phone' => array('field' => 'phone'),
        'sep2' => array('field' => 'seperator', 'label' => 'Additional'),
        'require_2fa' => array('field' => 'select', 'label' => 'Require E-Mail 2FA?', 'value' => 0, 'data_source' => 'hash:core:2fa_options'),
        'require_2fa_phone' => array('field' => 'select', 'label' => 'Require Phone 2FA?', 'value' => 0, 'data_source' => 'hash:core:2fa_options'),
        'language' => array('field' => 'select', 'selected' => 'en', 'required' => 1, 'data_source' => 'stdlist:language:1'),
        'timezone' => array('field' => 'select', 'selected' => 'PST', 'required' => 1, 'data_source' => 'stdlist:timezone')
    );

    // Add submit button
    $record_id = $data['record_id'] ?? 0;
    if ($record_id > 0) { 
        $form_fields['submit'] = array('field' => 'submit', 'value' => 'update', 'label' => 'Update Administrator');
    } else { 
        $form_fields['submit'] = array('field' => 'submit', 'value' => 'create', 'label' => 'Create New Administrator');
    }

    // Return
    return $form_fields;

}

/**
 * Get record from database. 
 *
 * Gathers the necessary row from the database for a specific record ID, and 
 * is used to populate the form fields.  Used when modifying a record. 
 *
 * @param string $record_id The value of the 'record_id' attribute from the e:function tag.
 *
 * @return array An array of key-value pairs containg the values of the form fields.
 */
public function get_record(string $record_id):array
{ 

    // Get row
    $row = db::get_idrow('admin', $record_id);
    $row['password'] = '';

    // Return
    return $row;

}

/**
 * Perform additional form validation. 
 *
 * On top of the standard form validation checks such as required fields, data 
 * types, min / max length, and so on, you can also perform additional 
 * validation for this specific form via this method.  Simply add the needed 
 * error callouts via the template->add_callout() method for any validation 
 * errors. 
 *
 * @param array $data Any array of data passed to the app::validate_form() method.  Used
 */
public function validate(array $data = array())
{ 

    // Create admin checks
    if (app::get_action() == 'create') { 

        // Check passwords confirm
        if (app::_post('password') != app::_post('confirm-password')) { 
            view::add_callout("Passwords do not match.  Please try again.", 'error');
        }

        // Check if username exists
        if ($id = db::get_field("SELECT id FROM admin WHERE username = %s", app::_post('username'))) { 
            view::add_callout(tr("The username already exists for an administrator, %s", app::_post('username')), 'error');
        }
    }


}


}

