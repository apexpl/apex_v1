<?php
declare(strict_types = 1);

namespace apex\core\worker;

use apex\app;
use apex\svc\db;
use apex\app\interfaces\msg\EventMessageInterface;


class logs
{




/**
 * Add login history 
 *
 * @param EventMessageInterface $msg The message that was dispatched.
 */
public function add_auth_login(EventMessageInterface $msg) { 

    // Initialize
    $vars = $msg->get_request();

    // Add to DB
    db::insert('auth_history', array(
        'type' => ($vars['area'] == 'admin' ? 'admin' : 'user'),
        'userid' => $vars['userid'],
        'ip_address' => $vars['ip_address'],
        'user_agent' => $vars['user_agent'],
        'logout_date' => date('Y-m-d H:i:s'))
    );
    $history_id = db::insert_id();

    // Return
    return $history_id;

}

/**
 * Add auth history page 
 * 
 * @param EventMessageInterface $msg The message that was dispatched.
 */
public function add_auth_pageview(EventMessageInterface $msg)
{ 

    // Decode JSON
    $vars = $msg->get_params();

    // Add to database
    db::insert('auth_history_pages', array(
        'history_id' => $vars['history_id'],
        'request_method' => $vars['request_method'],
        'uri' => $vars['uri'],
        'get_vars' => $vars['get_vars'],
        'post_vars' => $vars['post_vars'])
    );

}


}

