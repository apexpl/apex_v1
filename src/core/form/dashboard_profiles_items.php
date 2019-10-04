<?php
declare(strict_types = 1);

namespace apex\core\form;

use apex\app;
use apex\svc\db;
use apex\svc\debug;


/**
 * Dashboard profile items form.
 */
class dashboard_profiles_items
{

    // Properties
    public $allow_post_values = 1;

/**
 * Defines the form fields included within the HTML form.
 * 
 *   @param array $data An array of all attributes specified within the e:function tag that called the form. 
 *
 *   @return array Keys of the array are the names of the form fields.  Values of the array are arrays that specify the attributes of the form field.  Refer to documentation for details.
 */
public function get_fields(array $data = array()):array
{

    // Get profile
    if (!$row = db::get_idrow('dashboard_profiles', $data['profile_id'])) { 
        throw new ApexException('error', tr("Dashboard does not exist with ID#, {1}", $data['profile_id']));
    }

    // Set form fields
    $form_fields = array( 
        'item' => array('field' => 'select', 'required' => 1, 'data_source' => 'function:core:dashboard:create_item_options') 
    );

    // Add submit button
    if (isset($data['record_id']) && $data['record_id'] > 0) { 
        $form_fields['submit'] = array('field' => 'submit', 'value' => 'update_item', 'label' => 'Update Dashboard Item');
    } else { 
        $form_fields['submit'] = array('field' => 'submit', 'value' => 'add_item', 'label' => 'Add Dashboard Item');
    }

    // Return
    return $form_fields;

}

/**
 * Get values for a record.
 *
 * Method is called if a 'record_id' attribute exists within the 
 * a:function tag that calls the form.  Will retrieve the values from the 
 * database to populate the form fields with.
 *
 *   @param string $record_id The value of the 'record_id' attribute from the e:function tag.
 *
 *   @return array An array of key-value pairs containg the values of the form fields.
 */
public function get_record(string $record_id):array 
{

    // Get record
    $row = db::get_idrow('dashboard_profiles_items', $record_id);

    // Return
    return $row;

}

/**
 * Additional form validation.
 * 
 * Allows for additional validation of the submitted form.  
 * The standard server-side validation checks are carried out, automatically as 
 * designated in the $form_fields defined for this form.  However, this 
 * allows additional validation if warranted.
 *
 *     @param array $data Any array of data passed to the registry::validate_form() method.  Used to validate based on existing records / rows (eg. duplocate username check, but don't include the current user).
 */
public function validate(array $data = array()) 
{

    // Additional validation checks

}

}

