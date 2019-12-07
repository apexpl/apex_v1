<?php
declare(strict_types = 1);

namespace apex\core\htmlfunc;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\svc\view;
use apex\svc\components;
use apex\app\web\html_tags;
use apex\core\admin;


class notification_condition
{


    /**
     * @Inject
     * @var app
     */

    private $app;

    /**
     * @Inject
     * @var html_tags
     */

    private $html_tags;

    /**
     * @Inject
     * @var admin
     */

    private $admin;


/**
 * Replaces the calling <e:function> tag with the resulting string of this 
 * function. 
 *
 * @param string $html The contents of the TPL file, if exists, located at /views/htmlfunc/<package>/<alias>.tpl
 * @param array $data The attributes within the calling e:function> tag.
 *
 * @return string The resulting HTML code, which the <e:function> tag within the template is replaced with.
 */
public function process(string $html, array $data = array()):string
{ 

    // Initialize
    $html_tags = $this->html_tags;
    list($sender, $recipient, $condition) = array('', '', array());

    // Get values
    if (isset($data['notification_id']) && $row = DB::get_idrow('notifications', $data['notification_id'])) { 
        $condition = json_decode(base64_decode($row['condition_vars']), true);
        $sender = $row['sender'];
        $recipient = $row['recipient'];
        $data['controller'] = $row['controller'];
    }

    // Check controller exists
    if (!components::check('controller', 'core:notifications:' . $data['controller'])) { 
        return "<b>ERROR:</b> The notification controller '$data[controller]' does not exist.";
    }

    // Load component
    $client = components::load('controller', $data['controller'], 'core', 'notifications');

    // Create admin options
    $admin = $this->admin;
    $admin_options = $admin->create_select_options(0, true);

    // Sender option
    $sender_options = '';
    foreach ($client->senders as $key => $sender_name) { 
        if ($key == 'admin' && preg_match("/admin:(\d+)/", $sender, $match)) { $sender_options .= $admin->create_select_options((int) $match[1], true); }
        elseif ($key == 'admin') { $sender_options .= $admin_options; }
        else { 
            $chk = $key == $sender ? 'selected="selected"' : '';
            $sender_options .= "<option value=\"$key\" $chk>$sender_name</option>";
        }
    }

// Recipient options
    $recipient_options = '';
    foreach ($client->recipients as $key => $recipientr_name) { 
        if ($key == 'admin' && preg_match("/admin:(\d+)/", $key, $match)) { $recipient_options .= $admin->create_select_options((int) $match[1], true); }
        elseif ($key == 'admin') { $recipient_options .= $admin_options; }
        else { 
            $chk = $key == $recipient ? 'selected="selected"' : '';
            $recipient_options .= "<option value=\"$key\" $chk>$sender_name</option>";
        }
    }

    // Start HTML
    $html = $html_tags->ft_select(array('name' => 'sender', 'label' => 'Sender'), $sender_options);
    $html .= $html_tags->ft_select(array('name' => 'recipient', 'label' => 'Recipient'), $recipient_options);
    $html .= $html_tags->ft_seperator(array('label' => 'Condition Information'));

    // Get conditional HTML
    foreach ($client->fields as $field_name => $vars) { 
        $vars['name'] = 'cond_' .  $field_name;
        $func_name = 'ft_' . $vars['field'];
        $vars['value'] = isset($condition[$field_name]) ? $condition[$field_name] : '';
        $html .= $html_tags->$func_name($vars);
    }

    // Return
    $html = $html_tags->form_table(array(), $html);
    return $html;

}


}

