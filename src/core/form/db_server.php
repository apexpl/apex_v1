<?php
declare(strict_types = 1);

namespace apex\core\form;

use apex\app;
use apex\svc\redis;


class db_server 
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
        'dbname' => array('field' => 'textbox', 'label' => 'DB Name'),
        'dbuser' => array('field' => 'textbox', 'label' => 'DB Username'),
        'dbpass' => array('field' => 'textbox', 'label' => 'DB Password'),
        'dbhost' => array('field' => 'textbox', 'label' => 'DB Host'),
        'dbport' => array('field' => 'textbox', 'label' => 'DB Port', 'value' => '3306', 'width' => '60px')
    );

    // Add submit button
    if (isset($data['record_id'])) { 
        $form_fields['submit'] = array('field' => 'submit', 'value' => 'update_database', 'label' => 'Update Database');
    } else { 
        $form_fields['submit'] = array('field' => 'submit', 'value' => 'add_database', 'label' => 'Add Database');
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
    $data = redis::lindex('config:db_slaves', (int) $record_id);
    return json_decode($data, true);

}

}


