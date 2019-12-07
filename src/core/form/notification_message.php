<?php
declare(strict_types = 1);

namespace apex\core\form;

use apex\app;
use apex\svc\db;
use apex\svc\view;
use apex\core\notification;


class notification_message 
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


    // Get controller
    if (isset($data['record_id']) && $data['record_id'] > 0) { 
        $controller = db::get_field("SELECT controller FROM notifications WHERE id = %i", $data['record_id']);
    } else { 
        $controller = $data['controller'];
    }

    // Get merge fields
    $client = new notification();
    $merge_fields = $client->get_merge_fields($controller);
    view::assign('merge_variable_options', $merge_fields);

    // Define form fields
    $form_fields = array(
        'sep1' => array('field' => 'seperator', 'label' => 'Optional E-Mails'),
        'reply_to' => array('field' => 'textbox'),
        'cc' => array('field' => 'textbox'),
        'bcc' => array('field' => 'textbox'),
        'sep2' => array('field' => 'seperator', 'label' => 'Message Body'),
        'content_type' => array('field' => 'select', 'data_source' => 'hash:core:notification_content_type'),
        'subject' => array('field' => 'textbox', 'label' => 'Subject', 'required' => 1),
        'attachment' => array('field' => 'textbox', 'type' => 'file', 'label' => 'Attachment'),
        'merge_vars' => array('field' => 'custom', 'label' => 'Merge Variables', 'contents' =>"<select name=\"merge_vars\">~merge_variable_options~</select>  <button type=\"button\" onclick=\"addMergeField();\" class=\"btn btn-primary btn-md\">Add</button>"),
        'contents' => array('field' => 'textarea', 'label' => 'Message Contents', 'size' => '600px,300px', 'placeholder', 'Enter your message contents', 'required' => 1)
    );

    // Add submit button
    if (isset($data['record_id']) && $data['record_id'] > 0) { 
        $form_fields['submit'] = array('field' => 'submit', 'value' => 'update', 'label' => 'Update E-Mail Notification');
    } else { 
        $form_fields['submit'] = array('field' => 'submit', 'value' => 'create', 'label' => 'Create E_Mail Notification');
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
    $row = db::get_idrow('notifications', $record_id);
    $row['contents'] = base64_decode($row['contents']);

    // Return
    return $row;

}

}


