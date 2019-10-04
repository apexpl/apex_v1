<?php
declare(strict_types = 1);

namespace apex\core\form;

use apex\app;


class email_server 
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
        'email_is_ssl' => array('field' => 'boolean', 'value' => 0),
        'email_host' => array('field' => 'textbox', 'label' => 'Host'),
        'email_user' => array('field' => 'textbox', 'label' => 'Username'),
        'email_pass' => array('field' => 'textbox', 'label' => 'Password'),
        'email_port' => array('field' => 'textbox', 'label' => 'Port', 'value' => '25')
    );

    // Add submit button
    if (isset($data['record_id'])) { 
        $form_fields['submit'] = array('field' => 'submit', 'value' => 'update_email', 'label' => 'Update SMTP Server');
    } else { 
        $form_fields['submit'] = array('field' => 'submit', 'value' => 'add_email', 'label' => 'Add SMTP Server');
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

    // Get record
    $row = array();

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

    // Additional validation checks

}


}

