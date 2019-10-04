<?php
declare(strict_types = 1);

namespace apex\views;

use apex\app;
use apex\svc\db;
use apex\svc\view;
use apex\svc\redis;
use apex\svc\forms;
use apex\app\pkg\package_config;

/**
 * All code below this line is automatically executed when this template is viewed, 
 * and used to perform any necessary template specific actions.
 */


// Update general settings
if (app::get_action() == 'update_general') { 

    // Set vars
    $vars = array(
        'domain_name', 
        'date_format', 
        'nexmo_api_key', 
        'nexmo_api_secret', 
        'recaptcha_site_key', 
        'recaptcha_secret_key', 
        'openexchange_app_id', 
        'default_language', 
        'default_timezone', 
        'mode', 
        'log_level', 
        'debug_level', 
    );

    // Update config vars
    foreach ($vars as $var) { 
        app::update_config_var('core:' . $var, app::_post($var));
    }

    // User message
    view::add_callout("Successfully updated general settings");

// SIte info settings
} elseif (app::get_action() == 'site_info') { 

    // Set vars
    $vars = array(
        'site_name', 
        'site_address', 
        'site_address2', 
        'site_email', 
        'site_phone', 
        'site_tagline', 
        'site_facebook', 
        'site_twitter', 
        'site_linkedin', 
        'site_instagram' 
    );

    // Update config avrs
    foreach ($vars as $var) { 
        app::update_config_var('core:' . $var, app::_post($var));
    }

    // User message
    view::add_callout("Successfully updated site info settings");

// Security settings
} elseif (app::get_action() == 'security') { 

    // Set vars
    $vars = array(
        'session_expire_mins', 
        'password_retries_allowed', 
        'require_2fa',  
    );

    // Update config vars
    foreach ($vars as $var) { 
        app::update_config_var('core:' . $var, app::_post($var));
    }

    // Update date intervals
    app::update_config_var('core:session_retain_logs', forms::get_date_interval('session_retain_logs'));
    app::update_config_var('core:force_password_reset_time', forms::get_date_interval('force_password_reset_time'));

    // User message
    view::add_callout("Successfully updated admin panel security settings");

// Add database
} elseif (app::get_action() == 'add_database') { 

    // Set vars
    $vars = array(
        'dbname' => app::_post('dbname'), 
        'dbuser' => app::_post('dbuser'), 
        'dbpass' => app::_post('dbpass'), 
        'dbhost' => app::_post('dbhost'), 
        'dbport' => app::_post('dbport')
    );
    redis::rpush('config:db_slaves', json_encode($vars));

    // User message
    view::add_callout("Successfully added new database server");

// Update database
} elseif (app::get_action() == 'update_database') { 

    // Set variables
    $server_id = (int) app::_post('server_id');

    // Set vars
    $vars = array(
        'dbname' => app::_post('dbname'), 
        'dbuser' => app::_post('dbuser'), 
        'dbpass' => app::_post('dbpass'), 
        'dbhost' => app::_post('dbhost'), 
        'dbport' => app::_post('dbport')
    );

    // Save to redis
    redis::lset('config:db_slaves', $server_id, json_encode($vars));

    // User message
    view::add_callout("Successfully updated database server");

// Delete databases
} elseif (app::get_action() == 'delete_database') { 

    // Get IDs
    $ids = forms::get_chk('db_server_id');
    $slaves = redis::lrange('config:db_slaves', 0, -1);
    $new_slaves = array();
    // Delete databases
    $num=0;
    foreach ($slaves as $data) { 
        if (!in_array($num, $ids)) { $new_slaves[] = $data; }
        $num++;
    }

    // Reset slave servers
    redis::del('config:db_slaves');
    foreach ($new_slaves as $data) { 
        redis::rpush('config:db_slaves', $data);
    }

    // User message
    view::add_callout("Successfully deleted checked database servers");

// Add SMTP e-mail server
} elseif (app::get_action() == 'add_email') { 

    // Set vars
    $vars = array(
        'is_ssl' => app::_post('email_is_ssl'), 
        'host' => app::_post('email_host'), 
        'username' => app::_post('email_user'), 
        'password' => app::_post('email_pass'), 
        'port' => app::_post('email_port')
    );

    // Add to redis
    redis::rpush('config:email_servers', json_encode($vars));

    // Add message
    view::add_callout("Successfully added new SMTP e-mail server");

// Update e-mail SMTP server
} elseif (app::get_action() == 'update_email') { 

    // Set vars
    $vars = array(
        'is_ssl' => app::_post('email_is_ssl'), 
        'host' => app::_post('email_ost'), 
        'username' => app::_post('email_user'), 
        'password' => app::_post('email_pass'), 
        'port' => app::_post('email_port')
    );

    // Update redis database
    redis::lset('config:email_servers', app::_post('server_id'), json_encode($vars));

    // User message
    view::add_callout("Successfully updated e-mail SMTP server");

// Delete e-mail SMTP servers
} elseif (app::get_action() == 'delete_email') {

    // Get IDs
    $ids = forms::get_chk('email_server_id');
    $servers = redis::lrange('config:email_servers', 0, -1);
    $new_servers = array();

    // Delete e-mail servers
    $num=0;
    foreach ($servers as $data) { 
        if (!in_array($num, $ids)) { $new_servers[] = $data; }
        $num++;
    }

    // Reset e-mail servers
    redis::del('config:email_servers');
    foreach ($new_servers as $data) { 
        redis::rpush('config:email_servers', $data);
    }

    // User message
    view::add_callout("Successfully deleted all checked e-mail SMTP servers");

// Update RabbitMQ info
} elseif (app::get_action() == 'update_rabbitmq') { 

    // Set vars
    $vars = array(
        'host' => app::_post('rabbitmq_host'), 
        'port' => app::_post('rabbitmq_port'), 
        'user' => app::_post('rabbitmq_user'), 
        'pass' => app::_post('rabbitmq_pass')
    );

    // Update redis
    redis::hmset('config:rabbitmq', $vars);

    // User message
    view::add_callout("Successuflly updated RabbitMQ connection info");

// Reset redis
} elseif (app::get_action() == 'reset_redis') { 

    // Check
    if (strtolower(app::_post('redis_reset')) != 'reset') { 
        view::add_callout("You did not enter RESET in the provided text box", 'error');
    } else { 

        // Go through packages
        $packages = db::get_column("SELECT alias FROM internal_packages");
        foreach ($packages as $alias) { 
            $client = new Package_config($alias);
            $pkg = $client->load();

            if (!method_exists($pkg, 'redis_reset')) { continue; }
            $pkg->redis_reset();
        }

        // User message
        view::add_callout("Successfully reset the redis database");
    }

}


// Get RabbitMQ info
if (!$rabbitmq_vars = redis::hgetall('config:rabbitmq')) { 
    $rabbitmq_vars = array(
        'host' => 'localhost', 
        'port' => '5672', 
        'user' => 'guest', 
        'pass' => 'guest'
    );
}

// Template variables
view::assign('rabbitmq', $rabbitmq_vars);

