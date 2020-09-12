<?php
declare(strict_types = 1);

namespace apex\app\sys;

use apex\app;
use apex\libc\{db, redis, io, debug};
use apex\app\pkg\{package_config, pkg_component, package, theme};
use apex\app\sys\repo;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
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

    // Install checks
    $errors = $this->install_checks();
    if (count($errors) > 0) {
        echo "One or more requirements are missing.  Please resolve the below errors and try the installation again.\n\n";
        foreach ($errors as $err) { echo "- $err\n"; }
        exit(0);
    }

    // Check for install.yml file
    if (file_exists(SITE_PATH . '/install.yml') || file_exists(SITE_PATH . '/install.yaml')) {
        $this->process_yaml_file();
    }

    // Echo header
    echo "------------------------------\n";
    echo "-- Apex Installation Wizard\n";
    echo "------------------------------\n\n";

    // Get basic info
    $this->domain_name = $this->getvar("Domain Name [] [localhost]: ", 'localhost');
    $has_admin = $this->getvar('Enable Admin Panel (y/n) [y]: ', 'y');
    $has_javascript = $this->getvar('Enable Javascript (y/n) [y]: ', 'y');
    $this->enable_admin = strtolower($has_admin) == 'y' ? 1 : 0;
    $this->enable_javascript = strtolower($has_javascript) == 'y' ? 1 : 0;

    // Get redis info
    $this->get_redis_info();

    // Get mySQL info, if needed
    if (!redis::exists('config:db_master')) {
        $this->get_mysql_info();
    }

    // Complete install
    $this->complete_install();

    // Give welcome message
    $this->welcome_message();
    exit(0);

}

/**
 * Process YAML file
 */
public function process_yaml_file()
{

    // Initialize
    $file = file_exists(SITE_PATH . '/install.yml') ? 'install.yml' : 'install.yaml';

    // Parse file
    try {
        $vars = Yaml::parseFile(SITE_PATH . '/' . $file);
    } catch (ParseException $e) {
        die("Unable to parse $file file -- " . $e->getMessage());
    }

    // Get server type
    $this->server_type = $vars['server_type'] ?? 'all';
    if (!in_array($this->server_type, array('all', 'web', 'app', 'dbs', 'dbm'))) {
        die("Invalid server type defined within install.yml file, $this->server_type");
    }

    // Get domain name
    $this->domain_name = $vars['domain_name'] ?? '';
    if ($this->domain_name == '') {
        die("No domain specified within install.yml file");
    }

    // Get other basic variables
    $this->enable_admin = isset($vars['enable_admin']) && $vars['enable_admin'] == 0 ? 0 : 1;
    $this->enable_javascript = isset($vars['enable_javascript']) && $vars['enable_javascript'] == 0 ? 0 : 1;

    // Get redis info
    $redis = $vars['redis'] ?? [];
    $this->redis_host = $redis['host'] ?? 'localhost';
    $this->redis_port = $redis['port'] ?? 6379;
    $this->redis_pass = $redis['password'] ?? '';
    $this->redis_dbindex = $redis['dbindex'] ?? '0';

    // Connect to redis
    $this->connect_redis();

    // Get mySQL database info, if needed
    if (!redis::exists('config:db_master')) {

        // Get database driver
        $db = $vars['db'] ?? [];
        $this->db_driver = $db['driver'] ?? 'mysql';
        if (!file_exists(SITE_PATH . '/src/app/db/' . $this->db_driver . '.php')) {
            die("Invalid database driver, $this->db_driver");
        }

        // Set database variables
        $this->has_mysql = true;
        $this->type = isset($db['autogen']) && $db['autogen'] == 1 ? 'quick' : 'standard';
        $this->dbname = $db['dbname'] ?? '';
        $this->dbuser = $db['user'] ?? '';
        $this->dbpass = $db['password'] ?? '';
        $this->dbhost = $db['host'] ?? 'localhost';
        $this->dbport = $db['port'] ?? 3306;
        $this->dbroot_password = $db['root_password'] ?? '';
        $this->dbuser_readonly = $db['readonly_user'] ?? '';
        $this->dbpass_readonly = $db['readonly_password'] ?? '';

        // Generate random passwords, as needed
        if ($this->type == 'quick') {
            $this->dbpass = io::generate_random_string(24);
            $this->dbpass_readonly = io::generate_random_string(24);
        }

        // Complete mySQL setup
        $this->complete_mysql();
    }

    // Complete installation
    $this->complete_install();

    // Add repos
    $repos = $vars['repos'] ?? [];
    foreach ($repos as $host => $repo_vars) {
        $username = $repo_vars['user'] ?? '';
        $password = $repo_vars['password'] ?? '';

        // Add repo
        $client = app::make(repo::class);
        $client->add($host, $username, $password);
    }

    // Install packages
    $packages = $vars['packages'] ?? [];
    foreach ($packages as $alias) {
        $client = app::make(package::class);
        $client->install($alias);
    }

    // Install themes
    $themes = $vars['themes'] ?? [];
    foreach ($themes as $alias) {
        $client = app::make(theme::class);
        $client->install($alias);
    }

    // Configuration vars
    $config = $vars['config'] ?? [];
    foreach ($config as $key => $value) {
        app::update_config_var($key, $value);
    }

    // Welcome message, and exit
    $this->welcome_message();
    exit(0);

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
    $this->connect_redis();

}

/**
 * Connect to redis
 */
private function connect_redis()
{

    // Connect to redis
    $redis = new redisdb();
    if (!$redis->connect($this->redis_host, (int) $this->redis_port, 2)) {
        echo "Unable to connect to redis database using supplied information.  Please check the host and port, and try the installer again.\n\n";
        exit(0);
    }

    // Redis authentication, if needed
    if ($this->redis_pass != '' && !$redis->auth($this->redis_pass)) {
        echo "Unable to authenticate to redis with the provided password.  Please check the password, and try the installer again.\n\n";
        exit(0);
    }

    // Select redis db, if needed
    if ($this->redis_dbindex > 0) { $redis->select((int) $this->redis_dbindex); }

    // Set environment variables
    putEnv('redis_host=' . $this->redis_host);
    putEnv('redis_port=' . $this->redis_port);
    putEnv('redis_password=' . $this->redis_pass);
    putEnv('redis_dbindex=' . $this->redis_dbindex);

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

    // Conenct to RabbitMQ server
    $this->connect_rabbitmq();

}

/**
 * Connect to RabbitMQ server
 */
private function connect_rabbitmq()
{

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
    echo "-- SQL Database Information\n";
    echo "------------------------------\n\n";

    // Get database driver
    $this->db_driver = $this->getvar('Database Driver [mysql]: ', 'mysql');
    if (!file_exists("./src/app/db/" . $this->db_driver . ".php")) {
        echo "Invalid database driver, $this->db_driver.\n";
        exit(0);
    }

    // Set db driver
    app::set_db_driver($this->db_driver);
    $default_port = $this->db_driver == 'postgresql' ? 5432 : 3306;

    // Get install type
    if ($this->db_driver == 'mysql') {
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

    } else { $type = 'n'; }

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
        $this->dbport = (int) $this->getvar("Database Port [$default_port]:", (string) $default_port);

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
        $this->dbport = (int) $this->getvar("Database Port [$default_port]:", (string) $default_port);

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

    // Compelte mySQL
    $this->complete_mysql();


}

/**
 * Complete mySQL setup
 */
private function complete_mysql()
{

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
    if (version_compare(phpversion(), '7.4.0', '<') === true) {
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
        '.env',
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
        'gd',
        'zip'
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
    $client->scan_workers();

    // CHMOD directories
    chmod('./storage/logs', 0777);

    // Install components
    $this->install_components();

    // Execute PHP code, if needed
    if (method_exists($pkg, 'install_after')) {
        $pkg->install_after();
    }

    // Get .env file
    $config = base64_decode('CiMKIyBBcGV4IC5lbnYgZmlsZS4KIwojIEluIG1vc3QgY2FzZXMsIHlvdSBzaG91bGQgbmV2ZXIgbmVlZCB0byBtb2RpZnkgdGhpcyBmaWxlIGFzaWRlIGZyb20gdGhlIAojIHJlZGlzIGNvbm5lY3Rpb24gaW5mb3JtYXRpb24uICBUaGUgZXhjZXB0aW9uIGlzIGlmIHlvdSdyZSBydW5uaW5nIEFwZXggb24gIAojIGEgY2x1c3RlciBvZiBzZXJ2ZXJzLiAgVGhpcyBmaWxlIGFsbG93cyB5b3UgdG8gb3ZlcnJpZGUgdmFyaW91cyAKIyBzeXN0ZW0gY29uZmlndXJhdGlvbiB2YXJpYWJsZXMgZm9yIHRoaXMgc3BlY2lmaWMgc2VydmVyIGluc3RhbmNlLCBzdWNoIGFzIGxvZ2dpbmcgYW5kIGRlYnVnZ2luZyBsZXZlbHMuCiMKCiMgUmVkaXMgY29ubmVjdGlvbiBpbmZvcm1hdGlvbgpyZWRpc19ob3N0ID0gfnJlZGlzX2hvc3R+CnJlZGlzX3BvcnQgPSB+cmVkaXNfcG9ydH4KcmVkaXNfcGFzc3dvcmQgPSB+cmVkaXNfcGFzc34KcmVkaXNfZGJpbmRleCA9IH5yZWRpc19kYmluZGV4fgoKIyBFbmFibGUgYWRtaW4gcGFuZWw/ICgxPW9uLCAwPW9mZikKZW5hYmxlX2FkbWluID0gfmVuYWJsZV9hZG1pbn4KCiMgVGhlIG5hbWUgb2YgdGhpcyBpbnN0YW5jZS4gIENhbiBiZSBhbnl0aGluZyB5b3Ugd2lzaCwgCiMgYnV0IG5lZWRzIHRvIGJlIHVuaXF1ZSB0byB0aGUgY2x1c3Rlci4KO2luc3RhbmNlX25hbWUgPSBtYXN0ZXIKCiMgVGhlIHR5cGUgb2YgaW5zdGFuY2UsIHdoaWNoIGRldGVybWluZXMgaG93IHRoaXMgaW5zdGFuY2UgCiMgb3BlcmF0ZXMgKGllLiB3aGV0aGVyIGl0IHNlbmRzIG9yIHJlY2VpdmVzIHJlcXVlc3RzIHZpYSBSYWJiaXRNUSkuCiMKIyBTdXBwb3J0ZWQgdmFsdWVzIGFyZToKIyAgICAgYWxsICA9IEFsbC1pbi1PbmUgKGRlZmF1bHQpCiMgICAgIHdlYiAgPSBGcm9udC1lbmQgSFRUUCBzZXJ2ZXIKIyAgICAgYXBwICA9IEJhY2stZW5kIGFwcGxpY2F0aW9uIHNlcnZlcgojICAgICBtaXNjID0gT3RoZXIKIwo7c2VydmVyX3R5cGUgPSBhbGwKCiMgU2VydmVyIG1vZGUsIGNhbiBiZSAncHJvZCcgb3IgJ2RldmVsJwo7bW9kZSA9IGRldmVsCgojIExvZyBsZXZlbC4gIFN1cHBvcnRlZCB2YWx1ZXMgYXJlOgojICAgICBhbGwgPSBBbGwgbG9nIGxldmVscwojICAgICBtb3N0ID0gQWxsIGxldmVscywgZXhjZXB0ICdpbmZvJyBhbmQgJ25vdGljZScuCiMgICAgIGVycm9yX29ubHkgPSBPbmx5IGVycm9yIG1lc3NhZ2VzCiMgICAgIG5vbmUgPSBObyBsb2dnaW5nCjtsb2dfbGV2ZWwgPSBtb3N0CgojIERlYnVnIGxldmVsLiAgU3VwcG9ydGVkIHZhbHVlcyBhcmU6CiMgICAgIDAgPSBPZmYKIyAgICAgMSA9IFZlcnkgbGltaXRlZAojICAgICAyID0gTGltaXRlZAojICAgICAzID0gTW9kZXJhdGUKIyAgICAgNCA9IEV4dGVuc2l2ZQojICAgICA1ID0gVmVyeSBFeHRlbnNpdmUKO2RlYnVnX2xldmVsID0gMAoKCg==');
    $config = str_replace("~redis_host~", $this->redis_host, $config);
    $config = str_replace("~redis_port~", $this->redis_port, $config);
    $config = str_replace("~redis_pass~", $this->redis_pass, $config);
    $config = str_replace("~redis_dbindex~", $this->redis_dbindex, $config);
    $config = str_replace('~enable_admin~', $this->enable_admin, $config);
    file_put_contents(SITE_PATH . '/.env', $config);

    // Update redis config
    app::update_config_var('core:db_driver', $this->db_driver);
    app::update_config_var('core:cookie_name', io::generate_random_string(12));
    app::update_config_var('core:domain_name', $this->domain_name);
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

}

/**
 * Install components
 */
private function install_components()
{

    // Install components
    $files = io::parse_dir(SITE_PATH . '/src/core');
    foreach ($files as $file) {
        pkg_component::add_from_filename('core', "src/$file");
    }

    // Add views
    $views = io::parse_dir(SITE_PATH . '/views/tpl');
    foreach ($views as $view) {
        pkg_component::add_from_filename('core', "views/$file");
    }

}

/**
 * Give welcome message
 */
private function welcome_message()
{

    // Give success message
    $admin_url = 'http://' . $this->domain_name . '/admin/';
    echo "Thank you!  The Apex Platform has now been successfully installed on your server.\n\n";
    if ($this->server_type == 'all' || $this->server_type == 'app') {
        echo "To complete installation, please ensure the following crontab job is added.\n\n";
        echo "\t*/2 * * * * cd " . SITE_PATH . "; /usr/bin/php -q apex core.cron > /dev/null 2>&1\n\n";
        echo "You also need to place the Apex init script in its proper location by executing the following commands at the SSH prompt:\n\n";
        echo "\tsudo cp bootstrap/apex /etc/init.d/apex\n";
        echo "\tsudo ln -s /etc/init.d/apex /etc/rc3.d/S30apex\n\n";
    }
    echo "You may continue to your administration panel and create your first administrator by visiting:\n\n\t$admin_url\n\n";
    echo "You may also view all Apex documentation at: " . str_replace("/admin/", "/docs/", $admin_url) . "\n\n";

    // Return
    return true;

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
    echo "\n";
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
