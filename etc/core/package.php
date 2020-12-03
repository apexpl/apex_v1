<?php
declare(strict_types = 1);

namespace apex;

use apex\app;
use apex\libc\db;
use apex\libc\redis;
use apex\app\pkg\package;

/**
 * Configuration file for the main 'core' package of 
 * Apex.  This file contains all configuration fino such as external files, 
 * hashes, menus, and so on.
 */
class pkg_core
{

    /**
     * Basic package variables.  The $area can be either 'private', 
     * 'commercial', or 'public'.  If set to 'private', it will not appear on the public repository at all, and 
     * if set to 'commercial', you may define a price to charge within the $price variable below.
     */
    public $version = '1.5.34';
    public $access = 'public';
    public $price = 0;
    public $name = 'Core Framework';
    public $description = 'The core package of the framework, and is required for all installations of the software.';

    /**
     * Github / git Service Repository variables
     *
     * Only required if you wish to use the built-in Git integration within Apex.  These 
     * variables allow you to define the URL of the git repository, branch name, and if 
     * this is a forked repository, also the upstream repo URL.
     *
     * Once development of the package is complete, you may initialize the git 
     * repository of the package and commit it with:
     *     php apex.php git_init mtest
     * 
     * For full information on git integration, please refer to the documentation at:
     *     https://apex-platform.org/docs/github
     */
    public $git_repo_url = '';
    public $git_upstream_url = '';
    public $git_branch_name = 'master';


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
    'alias' => 'index', 
    'name' => 'Home'
);

    // External files
$this->ext_files = array(		
    'apex', 
    'composer.json',  
    'contribute.md', 
    'docker-compose.yml', 
    'install_example.yml', 
    'License.txt', 
    'phpunit.xml', 
    'Readme.md',
    'bootstrap/apex', 
    'bootstrap/cli.php', 
    'bootstrap/http.php', 
    'bootstrap/test.php',
    'bootstrap/docker/*',  
    'docs/components/*', 
    'docs/core/*', 
    'docs/guides/*', 
    'docs/training/*', 
    'etc/constants.php',
    'etc/preload.php', 
    'etc/core/stdlists',  
    'public/plugins/apex.js', 
    'public/themes/koupon/*',  
    'public/.htaccess', 
    'public/index.php', 
    'public/robots.txt', 
    'src/app.php', 
    'src/app/*', 
    'src/libc/*',
    'tests/Readme.md',    
    'views/themes/koupon/*' 
);


// Notifications
$this->notifications = array();
$this->notifications[] = array(
    'adapter' => 'system', 
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
        'log_level' => 'most', 
        'debug_level' => 0, 
        'max_logfile_size' => 4
    );
    $vars = array_merge($vars, $maintenance_vars);

    // Server vars
    $server_vars = array(
        'db_driver' => 'mysql', 
        'websocket_port' => 8194,  
        'enable_javascript' => 1, 
        'server_type' => 'all',
        'instance_name' => 'master', 
        'domain_name' => '', 
        'default_timezone' => 'PST', 
        'default_language' => 'en' 
    );
    $vars = array_merge($vars, $server_vars);

    // API update vars
    $update_vars = [
        'auto_upgrade' => 0, 
        'remote_api_key' => '', 
        'remote_api_host' => '', 
        'update_api_key' => '', 
        'update_api_enabled' => 0
    ];
    $vars = array_merge($vars, $update_vars);

    // Site info vars
    $site_vars = array(
        'site_name' => 'My Company Name', 
        'site_address' => '', 
        'site_address2' => '', 
        'site_email' => 'hello@apexpl.io', 
        'site_phone' => '',
        'site_tagline' => '', 
        'site_facebook' => '', 
        'site_twitter' => 'https://twitter.com/ApexPlatform', 
        'site_linkedin' => '', 
        'site_instagram' => '', 
        'site_youtube' => '', 
        'site_reddit' => '',
        'site_github' => '', 
        'site_dribble' => '', 
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
        'flysystem_credentials' => '[]', 
        'image_storage_type' => 'database'
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

    // Image storage types
    $vars['image_storage_types'] = [
        'database' => 'Database', 
        'filesystem' => 'Filesystem'
    ];

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
        'all' => 'All Levels', 
        'most' => 'All Levels, except INFO and NOTICE',
        'error_only' => 'Only Errors', 
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

    // Server types
    $vars['server_types'] = array(
        'all' => 'All-in-One', 
        'web' => 'Front-End Web Server', 
        'app' => 'Back-end Application Server', 
        'misc' => 'Other'
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
    //$pkg->update_redis_menus();

    // Setup stdlists again
    $this->install_after();


}
}

