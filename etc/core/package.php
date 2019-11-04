<?php

namespace apex;

use apex\app;
use apex\svc\redis;
use apex\app\pkg\package;

class pkg_core
{

// Set package variables
public $version = '1.2.10';
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
$this->config = array(
    'encrypt_cipher' => 'aes-256-cbc', 
    'encrypt_password' => '', 
    'encrypt_iv' => '', 
    'mode' => 'devel', 
    'debug' => 0, 
    'debug_level' => 3, 
    'cache' => 0, 
    'date_format' => 'F j, Y', 
    'start_year' => date('Y'),
    'server_type' => '',
    'server_name' => 'apex',  
    'theme_admin' => 'limitless', 
    'theme_public' => 'koupon', 
    'site_name' => 'Apex', 
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
    'domain_name' => '', 
    'session_expire_mins' => 60,  
    'session_retain_logs' => 'W2', 
    'password_retries_allowed' => 5, 
    'require_2fa' => 0, 
    'force_password_reset_time' => '', 
    'nexmo_api_key' => '', 
    'nexmo_api_secret' => '', 
    'recaptcha_site_key' => '', 
    'recaptcha_secret_key' => '', 
    'openexchange_app_id' => '', 
    'default_timezone' => 'PST', 
    'default_language' => 'en', 
    'log_level' => 'notice,error,critical,alert,emergency', 
    'debug_level' => 0, 
    'cookie_name' => 'K9dAmgkd4Uaf', 
    'backups_enable' => 1, 
    'backups_save_locally' => 1, 
    'backups_db_interval' => 'H3', 
    'backups_full_interval' => 'D1', 
    'backups_retain_length' => 'W1', 
    'backups_remote_service' => 'none', 
    'backups_aws_access_key' => '', 
    'backups_aws_access_secret' => '', 
    'backups_dropbox_client_id' => '', 
    'backups_dropbox_client_secret' => '', 
    'backups_dropbox_access_token' => '', 
    'backups_gdrive_client_id' => '', 
    'backups_gdrive_client_secret' => '', 
    'backups_gdrive_refresh_token' => '', 
    'backups_next_db' => 0, 
    'backups_next_full' => 0
);

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
 * Define the hashes.
 */
public function define_hashes() 
{

    $vars = array();

    // Require 2FA options
    $vars['2fa_options'] = array(
        0 => 'Disabled', 
        1 => 'Every Login Session', 
        2 => 'Only when New Device Recognized'
    );

    // Backup remote services
    $vars['backups_remote_services'] = array(
        'none' => 'None / Do Not Backup Remotely', 
        'aws' => 'Amazon Web Services', 
        'dropbox' => 'Dropbox', 
        'google_drive' => 'Google Drive', 
        'tarsnap' => 'Tarsnap'
    );


    // Server mode
    $vars['server_mode'] = array(
        'devel' => 'Development', 
        'prod' => 'Production'
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

    $vars['log_levels'] = array(
        'info,warning,notice,error,critical,alert,emergency' => 'All Levels', 
        'notice,error,critical,alert,emergency' => 'All Levels, except INFO and NOTICE',
        'error,critical,alert,emergency' => 'Only Errors', 
        'none' => 'No Logging'
    );

    $vars['debug_levels'] = array(
        0 => '0 - No Debugging', 
        1 => '1 - Very Limited', 
        2 => '2 - Limited', 
        3 => '3 - Medium', 
        4 => '4 - Extensive', 
        5 => '5 - Very Extensive'
    );


    // Boolean
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

    // Date formats
    $vars['date_formats'] = array(
        'F j, Y' => 'March 21, 2019', 
        'M-d-Y' => 'Mar-21-209', 
        'm/d/Y' => '3/21/2019', 
        'd/m/Y' => '21/3/2019', 
        'd-M-Y' => '21-Mar-2019' 
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

    // Secondary secure questions
    $vars['secondary_security_questions'] = ARRAy(
        'q1' => "What was your childhood nickname?", 
        'q2' => "In what city did you meet your spouse/significant other?", 
        'q3' => "What is the name of your favorite childhood friend?", 
        'q4' => "What street did you live on in third grade?", 
        'q5' => "What is your oldest sibling?s birthday month and year? (e.g., January 1900)", 
        'q6' => "What is the middle name of your oldest child?", 
        'q7' => "What is your oldest siblings middle name?", 
        'q8' => "What school did you attend for sixth grade?", 
        'q9' => "What was your childhood phone number including area code? (e.g., 000-000-0000)", 
        'q10' => "What is your oldest cousins first and last name?", 
        'q11' => "What was the name of your first stuffed animal?", 
        'q12' => "In what city or town did your mother and father meet?", 
        'q13' => "Where were you when you had your first kiss?", 
        'q14' => "What is the first name of the boy or girl that you first kissed?", 
        'q15' => "What was the last name of your third grade teacher?", 
        'q16' => "In what city does your nearest sibling live?", 
        'q17' => "What is your oldest brothers birthday month and year? (e.g., January 1900)", 
        'q18' => "What is your maternal grandmothers maiden name?", 
        'q19' => "In what city or town was your first job?", 
        'q20' => "What is the name of the place your wedding reception was held?", 
        'q21' => "What is the name of a college you applied to but didnt attend?" 
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

    // Return
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

