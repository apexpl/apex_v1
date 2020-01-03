<?php
declare(strict_types = 1);

namespace apex\app\sys;

use apex\app;
use apex\libc\db;
use apex\libc\redis;
use apex\libc\debug;
use apex\libc\io;
use apex\app\pkg\package_config;
use apex\app\pkg\pkg_component;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use redis as redisdb;


/** 
 * Handles the initial installation of Apex after it is 
 * downloaded.  This runs through the wizard, sets up the mySQL database and 
 * redis, etc.
 */
class installer 
{

    // Properties
    private $has_mysql = false;
    private $server_type = 'all';
    private $enable_admin = 1;
    private $enable_javascript = 1;

    // Redis connection info
    private $redis_host;
    private $redis_port;
    private $redis_pass;
    private $redis_dbindex;

    // RabbitMQ info
    private $rabbitmq_host = 'localhost';
    private $rabbitmq_port = 5672;
    private $rabbitmq_user = 'guest';
    private $rabbitmq_pass = 'guest';







/**
 * Run installation wizard.
 *
 * Runs the installation wizard, and is automatically executed from the 
 * the src/app.php script upon initialization if the software is not installed, 
 * and begins a standard installation of Apex.
 */
public function run_wizard() 
{

    // Make sure we're running via CLI
    if (php_sapi_name() != "cli") { 
        echo "This system is not yet installed, and can not be installed via the web browser.  Please login to your server, ahance to the installation directory, and type: php apex.php to initiate the installer.\n\n";
        exit(0);
    }

    // Echo header
    echo "------------------------------\n";
    echo "-- Apex Installation Wizard\n";
    echo "------------------------------\n\n";

    // Install checks
    $errors = $this->install_checks();
    if (count($errors) > 0) { 
        echo "One or more requirements are missing.  Please resolve the below errors and try the installation again.\n\n";
        foreach ($errors as $err) { echo "- $err\n"; }
        exit(0);
    }

    // Get server type
    echo "Choose a server type:\n\n";
    echo "      [all] All-in-One\n";
    echo "      [web] Front-End Web Server\n";
    echo "      [app] Back-End Application Server\n";
    echo "      [dbs] Database Slave Server\n";
    echo "      [dbm] Database Master Server\n";

    // Get server type
    $ok=false;
    do { 
        $this->server_type = $this->getvar("Server Type [all]: ", 'all');
        if (in_array($this->server_type, array('all', 'web', 'app', 'dbs', 'dbm'))) {
            $ok = true;
        } 
    } while ($ok = false);

    // Get other info
    $this->domain_name = $this->getvar("Domain Name []:", '');
    $has_admin = $this->getvar('Enable Admin Panel (y/n) [y]: ', 'y');
    $has_javascript = $this->getvar('Enable Javascript (y/n) [y]: ', 'y');
    $this->websocket_port = $this->getvar('WebSocket Server Port [8194]: ', '8194');
    $this->enable_admin = strtolower($has_admin) == 'y' ? 1 : 0;
    $this->enable_javascript = strtolower($has_javascript) == 'y' ? 1 : 0;

    // Get redis info
    $this->get_redis_info();

    // Get RabbitMQ info, if needed
    if ($this->server_type != 'all' && !redis::exists('config:rabbitmq')) { 
        $this->get_rabbitmq_info();
    }

    // Get mySQL info, if needed
    if (!redis::exists('config:db_master')) { 
        $this->get_mysql_info();
    }

    // Complete install
    $this->complete_install();

} 

/**
 * Get redis connection info
 */
private function get_redis_info()
{

    // Send header
    echo "------------------------------\n";
    echo "-- Redis Storage Engine\n";
    echo "------------------------------\n\n";

    // Get redis variables
    $this->redis_host = $this->getvar("Redis Host [localhost]:", 'localhost');
    $this->redis_port = (int) $this->getvar('Redis Port [6379]:', '6379');
    $this->redis_pass = $this->getvar("Redis Password []:", '');
    $this->redis_dbindex = (int) $this->getvar('Redis DB Index [0]:', '0');

    // Connect to redis
    $redis = new redisdb();
    if (!$redis->connect($this->redis_host, $this->redis_port, 2)) { 
        echo "Unable to connect to redis database using supplied information.  Please check the host and port, and try the installer again.\n\n";
        exit(0);
    }

    // Redis authentication, if needed
    if ($this->redis_pass != '' && !$redis->auth($this->redis_pass)) { 
        echo "Unable to authenticate to redis with the provided password.  Please check the password, and try the installer again.\n\n";
        exit(0);
    }

    // Select redis db, if needed
    if ($this->redis_dbindex > 0) { $redis->select($this->redis_dbindex); }

    // Define constants
    define('REDIS_HOST', $this->redis_host);
    define('REDIS_PORT', (int) $this->redis_port);
    define('REDIS_PASS', $this->redis_pass);
    define('REDIS_DBINDEX', $this->redis_dbindex);

    // Empty redis database
    $keys = $redis->keys('*');
    foreach ($keys as $key) { 
        $redis->del($key);
    }

    // Initialize app
    $app = new \apex\app('http');

}

/**
 * Get RabbitMQ info
 */
private function get_rabbitmq_info()
{

    // Send header
    echo "------------------------------\n";
    echo "-- RabbitMQ\n";
    echo "------------------------------\n\n";

    // Get RabbitMQ variables
    $this->rabbitmq_host = $this->getvar("RabbitMQ Host [localhost]:", 'localhost');
    $this->rabbitmq_port = $this->getvar("RabbitMQ Port: [5672]:", '5672');
    $this->rabbitmq_user = $this->getvar("RabbitMQ Username [guest]:", 'guest');
    $this->rabbitmq_pass = $this->getvar("RabbitMQ Password [guest]:", 'guest');

    // Test RabbitMQ connection
    if (!$connection = new AMQPStreamConnection($this->rabbitmq_host, $this->rabbitmq_port, $this->rabbitmq_user, $this->rabbitmq_pass)) { 
        echo "Unable to connect to the RabbitMQ server with the supplied information.  Please double check the information, and try the installer again.\n\n";
        exit(0);
    }

    // Set vars
    $vars = array(
        'host' => $this->rabbitmq_host, 
        'port' => $this->rabbitmq_port, 
        'user' => $this->rabbitmq_user, 
        'pass' => $this->rabbitmq_pass
    );

    // Add to redis
    redis::hmset('config:rabbitmq', $vars);

}

/**
 * Get mySQL connection info
 */
private function get_mysql_info()
{

    // Echo header
    echo "\n------------------------------\n";
    echo "-- mySQL Database Information\n";
    echo "------------------------------\n\n";

    // Get install type
    echo "Would you like to auto-generate the necessary mySQL database, users, and privileges?  This requires the root mySQL password, but it is only used once and not saved.  Thiss ";
    echo "option is recommended if possible, and it helps ensure all privileges are properly and securely set.\n\n";

    // Check install type
    $ok = false;
    do { 
        echo "Auto-generate mySQL database / users? (y/n): ";
        $type = strtolower(trim(readline()));
        if ($type != 'y' && $type != 'n') {
            echo "\nYou did not specify 'y' or 'n'.\n\n";
        } else { $ok = true; }
    } while ($ok === false);

    // Set variables
    $this->has_mysql = true;
    $this->type = $type == 'y' ? 'quick' : 'standard';

    // Gather necessary information -- Quick Install
    if ($this->type == 'quick') { 

        $this->dbname = $this->getvar("Desired Database Name:");
        $this->dbuser = $this->getvar("Desired Database Username:");
        $this->dbuser_readonly = $this->getvar("Desired Read-Only DB Username (optional):");
        $this->dbroot_password = $this->getvar("mySQL root Password:");
        $this->dbhost = $this->getvar("Database Host [localhost]:", 'localhost');
        $this->dbport = $this->getvar("Database Port [3306]:", '3306');

        // Set default values
        $this->dbpass = io::generate_random_string(24);
        $this->dbpass_readonly = io::generate_random_string(24);

    // Gather needed information -- Standard Install
    } else{ 

        // Get database info
        $this->dbname = $this->getvar("Database Name:");
        $this->dbuser = $this->getvar("Database Username:");
        $this->dbpass = $this->getvar("Database Password:");
        $this->dbhost = $this->getvar("Database Host [localhost]:", 'localhost');
        $this->dbport = (int) $this->getvar("Database Port [3306]:", '3306');

        // Read only mySQL user -- Header 
        echo "\n\nOptional, read only mySQL user.  Used for greater security as all connections to \n";
        echo "the database will be read only by default unless / until there is need for a write statement.  Requires root / super user privilege to thee database to setup privileges.\n\n";

        // Read only database user
        $this->dbuser_readonly = $this->getvar("Read-only DB Username []:");
        $this->dbpass_readonly = $this->getvar("Read-only DB Password []:");

        // Get root password, if needed
        if ($this->dbuser_readonly != '') { 
            $this->dbroot_password = $this->getvar("mySQL root Password:");
        } else { 
            $this->dbroot_password = ''; 
        }
    }

    // Set vars
    $vars = array(
        'dbname' => $this->dbname, 
        'dbuser' => $this->dbuser, 
        'dbpass' => $this->dbpass, 
        'dbhost' => $this->dbhost, 
        'dbport' => $this->dbport, 
        'dbuser_readonly' => $this->dbuser_readonly, 
        'dbpass_readonly' => $this->dbpass_readonly
    );
    redis::hmset('config:db_master', $vars);

    // Handle installs
    if ($this->type == 'quick') { 
        $this->handle_quick_install();
    } else { 
        $this->handle_standard_install();
    }
}

/**
 * Installation Checks
 *
 * Performs the necessary installation checks, and returns an array
 * of any errors found.  Automatically run during initialization of 
 * the installation wizard.
 */
private function install_checks() 
{

    // Initialize
    $errors = array();

    // Check PHP version
    if (version_compare(phpversion(), '7.2.0', '<') === true) { 
        $errors[] = "This software requires PHP v7.2+.  Please upgrade your PHP installation before continuing.";
    }

    // Check for Composer
    if (!file_exists(SITE_PATH . '/vendor/autoload.php')) { 
        $errors[] = "You have not updated the system via Composer yet.  Please type the following into terminal:  composer update\n";
    }

    // Try to create necessary directories, if they do not exist
    $chk_dirs = array(
        'storage/logs', 
        'storage/logs/services', 
        'views/components', 
        'views/components/htmlfunc', 
        'views/components/modal', 
        'views/components/tabpage'
    ); 
    foreach ($chk_dirs as $dir) { 
        if (!is_dir(SITE_PATH . '/' . $dir)) { @mkdir(SITE_PATH . '/' . $dir); }
    }

    // Check writable files / dirs
    $write_chk = array(
        'etc/config.php',    
        'storage/',
        'storage/logs', 
        'storage/logs/services'
    );
    foreach ($write_chk as $file) { 
        if (!is_writable(SITE_PATH . '/' . $file)) { $errors[] = "Unable to write to $file which is required."; }
    }

    // Set required extensions
    $extensions = array(
        'mysqli', 
        'openssl', 
        'curl', 
        'json', 
        'mbstring', 
        'redis', 
        'tokenizer', 
        'gd'
    );

    // Check PHP extensions
    foreach ($extensions as $ext) { 
        if (!extension_loaded($ext)) {
            $errors[] = "The PHP extension '$ext' is not installed, and is required.";
        }
    }

    // Return
    return $errors;

}

/**
 * Complete quick installation
 */
private function handle_quick_install() 
{

    // Connect to mySQL with root, and perform checks
    $root_conn = $this->quick_checks();
    $this->root_conn = $root_conn;

    // Create database
    if (!mysqli_query($root_conn, "CREATE DATABASE " . $this->dbname)) { 
        $this->install_error("Unable to create the database " . $this->dbname . ".  Please contact your server administrator."); 
    }

    // Create mySQL user
    if (!mysqli_query($root_conn, "CREATE USER '" . $this->dbuser . "'@'localhost' IDENTIFIED BY '" . $this->dbpass . "'")) { 
        $this->install_error("Unable to create the mySQL user " . $this->dbuser . ".  Please contact your server administrator to resolve the issue.");
    }

    // Creatte read-only user,, if needed
    if ($this->dbuser_readonly != '') {
        if (!mysqli_query($root_conn, "CREATE USER '" . $this->dbuser_readonly . "'@'localhost' IDENTIFIED BY '" . $this->dbpass_readonly . "'")) {  
            $this->install_error("Unable to create the mySQL user " . $this->dbuser_readonly . ".  Please contact your server administrator for further information.");
        }
    }

}

/**
 * Quick Installation checks (database, etc.)
 */
private function quick_checks()
{

    // Perform checks
    if ($this->dbname == '') { $this->install_error("You did not specify a database name.\n\n"); }
    if ($this->dbuser == '') { $this->install_error("You did not specify a desired database username.\n"); }

    // Check root connection
    if (!$root_conn = mysqli_connect('localhost', 'root', $this->dbroot_password, 'mysql', 3306)) { 
        $this->install_error("Unable to connect to the mySQL database with the provided root password.  Please double check, and try again.\n");
    }

    // Check if database name exists
    $result = mysqli_query($root_conn, "SHOW DATABASES");
    while ($row = mysqli_fetch_array($result)) { 
        if ($row[0] == $this->dbname) { $this->install_error("The database " . $this->dbname . " already exists.  Please try again with a database name that does not currently exist on the server."); }
    }

    // Check usernames
    $result = mysqli_query($root_conn, "SELECT user FROM user");
    while ($row = mysqli_fetch_array($result)) { 
        if ($row[0] == $this->dbuser) { $this->install_error("The mySQL user " . $this->dbuser . " already exists.  Please try again with a mySQL user that does not already exist."); }
        if ($this->dbuser_readonly != '' && $row[0] == $this->dbuser_readonly) { $this->install_error("The mySQL user " . $this->dbuser_readonly . " already exists.  Please try again with a username that does not already exist."); }
    }

    // Return
    return $root_conn;

}

/**
 * Handle a standard installation
 */
private function handle_standard_install()
{

    // Check main / read-only connection
    if (!$conn = mysqli_connect($this->dbhost, $this->dbuser, $this->dbpass, $this->dbname, $this->dbport)) { 
        $this->install_error("Unable to connect to the mySQL database with the supplied information.  Please double check, and try again.");
    }

    // Check read-only connection
    if ($this->dbuser_readonly != '') { 
        if (!$user_conn = mysqli_connect($this->dbhost, $this->dbuser_readonly, $this->dbpass_readonly, $this->dbname, $this->dbport)) { 
            $this->install_error("Unable to connect to the mySQL database using the provided information for the read-only account.");
        }
    } else { $user_conn = $conn; }

    // Check root connection
    if ($this->dbroot_password != '') { 
        if (!$root_conn = mysqli_connect($this->dbhost, 'root', $this->dbroot_password, 'mysql', $this->dbport)) { 
            $this->install_error("Unable to connect to myQL using the supplied root password.  Please try again.");
        }
        $this->root_conn = $root_conn;
    }

}

/**
 * Completes the installation,  Creates the mySQL database, 
 * write the /etc/config.php file, and more.
 */
private function complete_install() 
{

    // If we're setting up mySQL
    if ($this->has_mysql === true) { 

        // Grant privileges, as needed
        $this->grant_privs();

        // Populate database
        io::execute_sqlfile(SITE_PATH . '/etc/core/install.sql');
    }

    // Load package
    $client = new package_config('core');
    $pkg = $client->load();

    // Update version
    db::update('internal_packages', array('version' => $pkg->version), "alias = %s", 'core');

    // Execute PHP, if needed
    if (method_exists($pkg, 'install_before')) { 
        $pkg->install_before();
    }

    // Load configuration
    $client->install_configuration();
    $client->install_notifications($pkg);

    // CHMOD directories
    chmod('./storage/logs', 0777);

    // Install components
    $components = json_decode(file_get_contents(SITE_PATH . '/etc/core/components.json'), true);
    foreach ($components as $row) {
        if ($row['type'] == 'view') { $comp_alias = $row['alias']; }
        else { $comp_alias = $row['parent'] == '' ? 'core:' . $row['alias'] : 'core:' . $row['parent'] . ':' . $row['alias']; }

        pkg_component::add($row['type'], $comp_alias, $row['value'], (int) $row['order_num'], 'core');
    }

    // Execute PHP code, if needed
    if (method_exists($pkg, 'install_after')) { 
        $pkg->install_after();
    }

    // Get config.php file
    $config = base64_decode('PD9waHAKCi8vIFJlZGlzIGNvbm5lY3Rpb24KZGVmaW5lKCdSRURJU19IT1NUJywgJ35yZWRpc19ob3N0ficpOwpkZWZpbmUoJ1JFRElTX1BPUlQnLCB+cmVkaXNfcG9ydH4pOwpkZWZpbmUoJ1JFRElTX1BBU1MnLCAnfnJlZGlzX3Bhc3N+Jyk7CmRlZmluZSgnUkVESVNfREJJTkRFWCcsIH5yZWRpc19kYmluZGV4fik7CgpkZWZpbmUoJ0VOQUJMRV9BRE1JTicsIH5lbmFibGVfYWRtaW5+KTsKCgo=');
    $config = str_replace("~redis_host~", $this->redis_host, $config);
    $config = str_replace("~redis_port~", $this->redis_port, $config);
    $config = str_replace("~redis_pass~", $this->redis_pass, $config);
    $config = str_replace("~redis_dbindex~", $this->redis_dbindex, $config);
    $config = str_replace('~enable_admin~', $this->enable_admin, $config);
    file_put_contents(SITE_PATH . '/etc/config.php', $config);

    // Update redis config
    app::update_config_var('core:cookie_name', io::generate_random_string(12));
    app::update_config_var('core:server_type', $this->server_type);
    app::update_config_var('core:domain_name', $this->domain_name);
    app::update_config_var('core:websocket_port', $this->websocket_port);
    app::update_config_var('core:enable_javascript', $this->enable_javascript);

    // Set encryption info
    app::update_config_var('core:encrypt_cipher', 'aes-256-cbc');
    app::update_config_var('core:encrypt_password', base64_encode(openssl_random_pseudo_bytes(32)));
    app::update_config_var('core:encrypt_iv', io::generate_random_string(16));

    // Create apex init file
    $init_file = file_get_contents(SITE_PATH . '/bootstrap/apex');
    $init_file = str_replace("~site_path~", SITE_PATH, $init_file);
    file_put_contents(SITE_PATH . '/bootstrap/apex', $init_file);
    chmod(SITE_PATH . '/bootstrap/apex', 0755);
    chmod(SITE_PATH . '/apex', 0755);

    // Give success message
    $admin_url = 'http://' . $this->domain_name . '/admin/';
    echo "Thank you!  The Apex Platform has now been successfully installed on your server.\n\n";
    if ($this->server_type == 'all' || $this->server_type == 'app') { 
        echo "To complete installation, please ensure the following crontab job is added.\n\n";
        echo "\t*/5 * * * * /cd " . SITE_PATH . "; /usr/bin/php -q apex.php core.cron > /dev/null 2>&1\n\n";
        echo "You also need to place the Apex init script in its proper location by executing the following commands at the SSH prompt:\n\n";
        echo "\tsudo cp bootstrap/apex /etc/init.d/apex\n";
        echo "\tsudo ln -s /etc/init.d/apex /etc/rc3.d/S30apex\n\n";
    }
    echo "You may continue to your administration panel and create your first administrator by visiting:\n\n\t$admin_url\n\n";
    echo "You may also view all Apex documentation at: " . str_replace("/admin/", "/docs/", $admin_url) . "\n\n";

    // Exit
    exit(0);

}

/**
* Give off an installation error
 *
* @param string $message The error message.
*/
private function install_error(string $message) 
{

    echo "Error: $message\n\n";
    exit(0);
}

/**
* Get a variable from the readline prompt.
 * @param string $label The label of the variable.
 * @param string $default_value The default value if user does not specify a value.
*/
private function getvar(string $label, string $default_value = '') 
{ 
    echo "$label ";
    $value = trim(readline());
    if ($value == '') { $value = $default_value; }
    return $value;
}

/**
* Grants the necessary privilegs to the mySQL database, 
* assuming we have a root password and connection.
*/

private function grant_privs()
{

    // Ensure we have root connection
    if (!isset($this->root_conn)) { return; }

    // Get privilege SQL
    $priv_sql = $this->get_priv_sql();

    // Grant privileges
    foreach ($priv_sql as $sql) { 
        if (!mysqli_query($this->root_conn, $sql)) { $this->install_error("Unable to set mySQL privileges as necessary."); }
    }

}

/**
* Returns the SQL statements to grant necessary privileges, and 
* used when taking advantage of the read-only 
* mySQL user.
*/
private function get_priv_sql() 
{

    // Set SQL
    $sql = array();

    // Grant "all" to database
    if ($this->type == 'quick') { 
        $user = $this->dbuser;
        $sql[] = "GRANT ALTER, CREATE, DELETE, DROP, INDEX, INSERT, LOCK TABLES, REFERENCES, SELECT, UPDATE ON " . $this->dbname . ".* TO '" . $user . "'@'" . $this->dbhost . "'";
    }

    // Add read-only privileges
    if ($this->dbuser_readonly != '') { 
        $sql[] = "GRANT SELECT ON " . $this->dbname . ".* TO '" . $this->dbuser_readonly . "'@'" . $this->dbhost . "'";
    }

    // Add flush statement
    $sql[] = "FLUSH PRIVILEGES";

    // Return
    return $sql;

}

}

