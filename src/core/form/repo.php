<?php
declare(strict_types = 1);

namespace apex\core\form;

use apex\app;
use apex\svc\db;


class repo 
{




    public $allow_post_values = 0;

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
        'repo_is_ssl' => array('field' => 'boolean', 'label' => 'Is SSL?', 'value' => 1),
        'repo_host' => array('field' => 'textbox', 'label' => 'Hostname', 'placeholders' => 'repo.domai.com'),
    );

    // Check for local
    $is_local = $data['is_local'] ?? 0;
    if ($is_local == 1) { 
        $form_fields['repo_alias'] = array('field' => 'textbox', 'label' => 'Repo Alias', 'datatype' => 'alphanum', 'placeholder' => 'public');
        $form_fields['repo_name'] = array('field' => 'textbox', 'label' => 'Repo Name');
        $form_fields['repo_description'] = array('field' => 'textarea', 'label' => 'Description');
        $form_fields['sep_login'] = array('field' => 'seperator', 'label' => 'Login Credentials');
    }

    // Add login fields
    $form_fields['repo_username'] = array('field' => 'textbox', 'label' => 'Username');
    $form_fields['repo_password'] = array('field' => 'textbox', 'type' => 'password', 'label' => 'Password');

    // Add submit button
    if (isset($data['record_id'])) { 
        $form_fields['submit'] = array('field' => 'submit', 'value' => 'update_repo', 'label' => 'Update Repository');
    } else { 
        $form_fields['submit'] = array('field' => 'submit', 'value' => 'add_repo', 'label' => 'Add New Repository');
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
    if (!$row = db::get_idrow('internal_repos', $record_id)) { 
        $row = array();
    }

    // Format
    foreach ($row as $key => $value) { 
        $row['repo_' . $key] = $value;
    }

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

