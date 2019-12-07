<?php
declare(strict_types = 1);

namespace apex\core\form;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\app\exceptions\ApexException;


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
    $form_fields['submit'] = array('field' => 'submit', 'value' => 'add_item', 'label' => 'Add Dashboard Item');

    // Return
    return $form_fields;

}

}


