<?php
declare(strict_types = 1);

namespace apex\core\form;

use apex\app;
use apex\svc\db;


class cms_menus 
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
        'area' => array('field' => 'select', 'data_source' => 'hash:core:cms_menus_area'),
        'alias' => array('field' => 'textbox', 'label' => 'Alias / URI'),
        'name' => array('field' => 'textbox', 'label' => 'Page Title'),
        'order_num' => array('field' => 'textbox', 'width' => '60px'),
        'require_login' => array('field' => 'boolean', 'value' => 0),
        'require_nologin' => array('field' => 'boolean', 'value' => 0, 'label' => 'Require No Login'),
        'link_type' => array('field' => 'select', 'data_source' => 'hash:core:cms_menus_types'),
        'sep_addl' => array('field' => 'seperator', 'label' => 'Optional'),
        'parent' => array('field' => 'textbox', 'label' => 'Parent Menu Alias / URI'),
        'icon' => array('field' => 'textbox'),
        'url' => array('field' => 'textbox', 'label' => 'External URL')
    );

    // Add submit
    if (isset($data['record_id'])) { 
        $form_fields['submit'] = array('field' => 'submit', 'value' => 'update_menu', 'label' => 'Update Menu');
        $area = db::get_field("SELECT area FROM cms_menus WHERE id = %i", $data['record_id']);
    } else { 
        $form_fields['submit'] = array('field' => 'submit', 'value' => 'add_menu', 'label' => 'Add New Menu');
        $area = $data['area'] ?? 'public';
    }

    // Set variables
    if ($area != 'public') { 
        unset($form_fields['require_login']);
        unset($form_fields['require_nologin']);
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
    $row = db::get_idrow('cms_menus', $record_id);

    // Return
    return $row;

}

}


