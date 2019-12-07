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
    $form_fields['submit'] = array('field' => 'submit', 'value' => 'add_email', 'label' => 'Add SMTP Server');

    // Return
    return $form_fields;

}

}


