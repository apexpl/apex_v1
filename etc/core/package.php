<?php

namespace apex;

use apex\app;
use apex\svc\redis;
use apex\app\pkg\package;

class pkg_core
{

// Set package variables
public $version = '1.2.14';
public $access = 'public';
public $name = 'Core Framework';
public $description = 'The core package of the framework, and is required for all installations of the software.';

/**
 * Define the base configuration of the package, 
 * including configuration variables, hashes, menus, 
 * external files, etc.
 */
public function __construct() 
{

// Config variables
$this->config = $this->define_config();

// Hashes
$this->hash = $this->define_hashes();

// Public menus
$this->menus = array();
$this->menus[] = array(
    'area' => 'public', 
    'position' => 'top', 
    'menus' => array(
        'index'=> 'Home', 
        'about' => 'About Us', 
        'register' => 'Sign Up', 
        'login' => 'Login', 
        'contact' => 'Contact Us'
    )
);

// Admin menu -- Setup header
$this->menus[] = array(
    'area' => 'admin', 
    'position' => 'top', 
    'type' => 'header', 
    'alias' => 'hdr_setup', 
    'name' => 'Setup'
);

// Admin menus -- Setup
$this->menus[] = array(
    'area' => 'admin', 
    'position' => 'after hdr_setup', 
    'type' => 'parent', 
    'icon' => 'fa fa-fw fa-cog', 
    'alias' => 'settings', 
    'name' => 'Settings', 
    'menus' => array(
        'general' => 'General' 
    )
);



    // External files
$this->ext_files = array(		
    'apex', 
    'apex.php',
    'composer.json',  
    'License.txt', 
    'phpunit.xml', 
    'Readme.md',
    'bootstrap/apex', 
    'bootstrap/cli.php', 
    'bootstrap/http.php', 
    'bootstrap/test.php', 
    'docs/components/*', 
    'docs/core/*', 
    'docs/training/*', 
    'etc/constants.php',
    'etc/core/stdlists',  
    'public/plugins/apex.js', 
    'public/plugins/flags/*', 
    'public/plugins/parsley.js/*', 
    'public/plugins/sounds/notify.wav', 
    'public/themes/koupon/*',  
    'public/themes/limitless/*', 
    'public/themes/atlant_members/*', 
    'public/.htaccess', 
    'public/index.php', 
    'src/app.php', 
    'src/app/*', 
    'src/svc/*',  
    'tests/core/*', 
    'views/themes/koupon/*', 
    'views/themes/limitless/*', 
    'views/themes/atlant_members/*' 
);


// Notifications
$this->notifications = array();
$this->notifications[] = array(
    'controller' => 'system', 
    'sender' => 'admin:1', 
    'recipient' => 'user', 
    'content_type' => 'text/plain', 
    'subject' => '2FA Required - ~site_name~', 
    'contents' => 'CkEgcmVjZW50IGFjdGlvbiBpbml0aWF0ZWQgYnkgeW91ciB1c2VyIGFjY291bnQgb24gfnNpdGVfbmFtZX4gcmVxdWlyZXMgdHdvIGZhY3RvciBhdXRoZW50aWNhdGlvbi4gIFRvIGNvbnRpbnVlIHdpdGggdGhpcyBhY3Rpb24sIHBsZWFzZSBjbGljayBvbiB0aGUgYmVsb3cgbGluay4KCiAgICB+MmZhLXVybH4KClRoYW5rIHlvdSwKfnNpdGVfbmFtZX4KCgoK', 
    'cond_action' => '2fa'
);

// Dashboard items
$this->dashboard_items = array(
    array(
        'area' => 'admin', 
        'type' => 'top',
        'divid' => 'members-online',  
        'panel_class' => 'panel bg-teal-400', 
        'is_default' => 1, 
        'alias' => 'admins_online', 
        'title' => 'Admins Online', 
        'description' => 'The total number of administrators in currently online.'
    ), 
    array(
        'area' => 'admin', 
        'type' => 'right', 
        'is_default' => 1, 
        'alias' => 'blank', 
        'title' => 'Blank / Default', 
        'description' => 'Blank item used until other packages are installed' 
    ), 
    array(
        'area' => 'admin', 
        'type' => 'tab',
        'is_default' => 1,  
        'alias' => 'blank', 
        'title' => 'Core - Blank / Default', 
        'description' => 'A blank item used as a default before other packages are installed.'
    )
);
 



}

/**
 * Define config vars
 */
private function define_config()
{

    // General vars
    $vars = array(
        'encrypt_cipher' => 'aes-256-cbc', 
        'encrypt_password' => '', 
        'encrypt_iv' => '', 
        'cookie_name' => 'K9dAmgkd4Uaf', 
        'start_year' => date('Y'),
        'date_format' => 'F j, Y',
        'theme_admin' => 'limitless', 
        'theme_public' => 'koupon'
    );

    // Maintenance vars
    $maintenance_vars = array(
        'mode' => 'devel', 
        'debug' => 0, 
        'cache' => 0,
        'log_level' => 'notice,error,critical,alert,emergency', 
        'debug_level' => 0
    );
    $vars = array_merge($vars, $maintenance_vars);

    // Server vars
    $server_vars = array(
        'server_type' => '',
        'server_name' => 'apex',  
        'domain_name' => '', 
        'default_timezone' => 'PST', 
        'default_language' => 'en'
    );
    $vars = array_merge($vars, $server_vars);

    // Site info vars
    $site_vars = array(
        'site_name' => 'My Company Name', 
        'site_address' => '', 
        'site_address2' => '', 
        'site_email' => 'support@envrin.com', 
        'site_phone' => '',
        'site_tagline' => '', 
        'site_facebook' => '', 
        'site_twitter' => 'https://twitter.com/DizakMatt', 
        'site_linkedin' => '', 
        'site_instagram' => '', 
        'site_youtube' => '', 
        'site_reddit' => '',
        'site_about_us' => 'You may specify the text for this section through the Settings->General->Site Info tab of the administration panel.'
    );
    $vars = array_merge($vars, $site_vars);

    // Security vars
    $security_vars = array(
        'session_expire_mins' => 60,  
        'session_retain_logs' => 'W2', 
        'password_retries_allowed' => 5, 
        'require_2fa' => 0, 
        'force_password_reset_time' => ''
    );
    $vars = array_merge($vars, $security_vars);

    // API vars
    $api_vars = array(
        'nexmo_api_key' => '', 
        'nexmo_api_secret' => '', 
        'recaptcha_site_key' => '', 
        'recaptcha_secret_key' => '', 
        'openexchange_app_id' => '', 
        'twitter_api_key' => ''
    );
    $vars = array_merge($vars, $api_vars);

    // Backup settings
    $backup_vars = array(
        'backups_enable' => 1,  
        'backups_db_interval' => 'H3', 
        'backups_full_interval' => 'D1', 
        'backups_retain_length' => 'W1', 
        'backups_next_db' => 0, 
        'backups_next_full' => 0
    );
    $vars = array_merge($vars, $backup_vars);

    // Flysystem storage vars
    $flysystem_vars = array(
        'flysystem_type' => 'local', 
        'flysystem_credentials' => '[]'
    );
    $vars = array_merge($vars, $flysystem_vars);

    // Return
    return $vars;


}


/**
 * Define the hashes.
 */
private function define_hashes() 
{

    // General
    $vars = $this->define_hashes_general();

    // Settings
    $settings = $this->define_hashes_settings();
    $vars = array_merge($vars, $settings);

    // System
    $system = $this->define_hashes_system();
    $vars = array_merge($vars, $system);

    // Return
    return $vars;

}

/**
 * Hashes -- General
 */
private function define_hashes_general()
{

    // Boolean
    $vars = array();
    $vars['boolean'] = array(
        '1' => 'Yes',
        '0' => 'No'
    );

    // Time intervals
    $vars['time_intervals'] = array(
        'I' => 'Minute', 
        'H' => 'Hour', 
        'D' => 'Day', 
        'W' => 'Week', 
        'M' => 'Month', 
        'Y' => 'Year'
    );

    // Form fields
    $vars['form_fields'] = array(
        'textbox' => 'Textbox', 
        'textarea' => 'Textarea', 
        'select' => 'Select List', 
        'radio' => 'Radio List', 
        'checkbox' => 'Checkbox List', 
        'boolean' => 'Boolean (yes/no)'
    );

    // CMS - menu areas
    $vars['cms_menus_area'] = array(
        'public' => 'Public Site', 
        'members' => 'Member Area'
    );

    // CMS menu types
    $vars['cms_menus_types'] = array(
        'internal' => 'Internal Page', 
        'external' => 'External URL', 
        'parent' => 'Parent Menu', 
        'header' => 'Header / Seperator'
    );

    // Return
    return $vars;

}

/**
 * Hashes -- Settings
 */
private function define_hashes_settings()
{

    // 2FA Options
    $vars = array();
    $vars['2fa_options'] = array(
        0 => 'Disabled', 
        1 => 'Every Login Session', 
        2 => 'Only when New Device Recognized'
    );

    // Date formats
    $vars['date_formats'] = array(
        'F j, Y' => 'March 21, 2019', 
        'M-d-Y' => 'Mar-21-209', 
        'm/d/Y' => '3/21/2019', 
        'd/m/Y' => '21/3/2019', 
        'd-M-Y' => '21-Mar-2019' 
    );

    // File storage types
    $vars['storage_types'] = array(
        'local' => 'Local Server', 
        'sftp' => 'sFTP', 
        'aws3' => 'Amazon Web Servers v3', 
        'digitalocean' => 'DigitalOcean Spaces', 
        'dropbox' => 'DropBox'
    );

    // Base currencies
    $vars['base_currencies'] = array(
        'AUD' => 'Australian Dollar (AUD)', 
        'BRL' => 'Brazilian Real (BRL)', 
        'GBP' => 'British Pound (GBP)', 
        'CAD' => 'Canadian Dollar (CAD)', 
        'CLP' => 'Chilean Peso (CLP)', 
        'CNY' => 'Chinese Yuan (CNY)', 
        'CZK' => 'Czech Koruna (CZK)', 
        'DKK' => 'Danish Krone (DKK)', 
        'EUR' => 'Euro (EUR)', 
        'HKD' => 'Hong Kong Dollar (HKD)', 
        'HUF' => 'Hungarian Forint (HUF)', 
        'INR' => 'Indian Rupee (INR)', 
        'IDR' => 'Indonesian Rupiah (IDR)', 
        'ILS' => 'Israeli New Shekel (ILS)', 
        'JPY' => 'Japanese Yen (JPY)', 
        'MYR' => 'Malaysian Ringgit (MYR)', 
        'MXN' => 'Mexican Peso (MXN)', 
        'NZD' => 'New Zealand Dollar (NZD)', 
        'NOK' => 'Norwegian Krone (NOK)', 
        'PKR' => 'Pakistani Rupee (PKR)', 
        'PHP' => 'Philippine Peso (PHP)', 
        'PLN' => 'Polish Zloty (PLN)', 
        'RUB' => 'Russian Ruble (RUB)', 
        'SGD' => 'Singapore Dollar (SGD)', 
        'ZAR' => 'South African Rand (ZAR)', 
        'KRW' => 'South Korean Won (KRW)', 
        'SEK' => 'Swedish Krona (SEK)', 
        'CHF' => 'Swiss Franc (CHF)', 
        'TWD' => 'Taiwan Dollar (TWD)', 
        'THB' => 'Thailand Baht (THB)', 
        'TRY' => 'Turkish Lira (TYR)', 
    );

    // System notification actions
    $vars['notify_system_actions'] = array(
        '2fa' => 'Two Factor Authentication (2FA)'
    );

    // Notification content type
    $vars['notification_content_type'] = array(
        'text/plain' => 'Plain Text', 
        'text/html' => 'HTML'
    );

    // Return
    return $vars;

}

/**
 * Hashes -- System
 */
private function define_hashes_system()
{
    $vars = array();
    // Server mode
    $vars = array();
    $vars['server_mode'] = array(
        'devel' => 'Development', 
        'prod' => 'Production'
    );

    // Log levels
    $vars['log_levels'] = array(
        'info,warning,notice,error,critical,alert,emergency' => 'All Levels', 
        'notice,error,critical,alert,emergency' => 'All Levels, except INFO and NOTICE',
        'error,critical,alert,emergency' => 'Only Errors', 
        'none' => 'No Logging'
    );

    // Debug levels
    $vars['debug_levels'] = array(
        0 => '0 - No Debugging', 
        1 => '1 - Very Limited', 
        2 => '2 - Limited', 
        3 => '3 - Medium', 
        4 => '4 - Extensive', 
        5 => '5 - Very Extensive'
    );

    // return
    return $vars;

}


/**
 * Install after.
 */
public function install_after() 
{

    // Delete keys from redis
    redis::del('std:language');
    redis::del('std:currency');
    redis::del('std:timezone');
    redis::del('std:country');

    $lines = file(SITE_PATH . '/etc/core/stdlists');
    foreach ($lines as $line) { 
        $vars = explode("::", base64_decode(trim($line)));

        if ($vars[0] == 'currency') { 
            $line = implode("::", array($vars[2], $vars[4], $vars[5]));
        redis::hset('std:currency', $vars[1], $line); 

        } elseif ($vars[0] == 'timezone') {  
        $line = implode("::", array($vars[2], $vars[3], $vars[4]));
            redis::hset('std:timezone', $vars[1], $line); 

        } elseif ($vars[0] == 'country') { 
            $line = implode("::", array($vars[2], $vars[3], $vars[4], $vars[5], $vars[6], $vars[7]));
            redis::hset('std:country', $vars[1], $line); 

        } elseif ($vars[0] == 'language') { 
            redis::hset('std:language', $vars[1], $vars[2]); 
        }

    }

    // Active languages
    redis::lpush('config:language', 'en');

}



////////////////////////////////////////////////////////////
// Reset
////////////////////////////////////////////////////////////

public function reset() { } 


////////////////////////////////////////////////////////////
// Remove
////////////////////////////////////////////////////////////

public function remove() { }

/**
* Reset the redis database
*/
public function reset_redis()
{

    // Delete needed keys
    redis::del('config:components');
    redis::del('config:components_package');
    redis::del('hash');
    redis::del('cms:titles');
    redis::del('cms:layouts');
    redis::del('cms:placeholders');

    // Go through all components
    $rows = DB::query("SELECT * FROM internal_components");
    foreach ($rows as $row) {

        // Add to components
        $line = implode(":", array($row['type'], $row['package'], $row['parent'], $row['alias']));
        redis::sadd('config:components', $line);

        // Add to components_package
        $chk = $row['type'] . ':' . $row['alias'];
        if ($value = redis::hget('config:components_package', $chk)) { 
            redis::hset('config:components_package', $chk, 2);
        } else { 
            redis::hset('config:components_package', $chk, $row['package']);
        }

        // Process hash, if needed
        if ($row['type'] == 'hash') {
            $hash_alias = $row['package'] . ':' . $row['alias']; 
            $vars = DB::get_hash("SELECT alias,value FROM internal_components WHERE type = 'hash_var' AND parent = %s AND package = %s", $row['alias'], $row['package']);
            redis::hset('hash', $hash_alias, json_encode($vars));
        }
    }

    // GO through CMS pages
    $rows = DB::query("SELECT * FROM cms_pages");
    foreach ($rows as $row) { 
        $key = $row['area'] . '/' . $row['filename'];
        redis::hset('cms:titles', $key, $row['title']);
        redis::hset('cms:layouts', $key, $row['layout']);
    }

    // CMS placeholders
    $rows = DB::query("SELECT * FROM cms_placeholders WHERE contents != ''");
    foreach ($rows as $row) { 
        $key = $row['uri'] . ':' . $row['alias'];
        redis::hset('cms_placeholders', $key, $row['contents']);
    }

    // CMS menus
    $pkg = new package('core');
    $pkg->update_redis_menus();

    // Setup stdlists again
    $this->install_after();


}
}

