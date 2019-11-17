<?php
declare(strict_types = 1);

namespace apex;

use apex\app;
use apex\svc\debug;
use apex\svc\db;
use apex\svc\redis;
use apex\svc\view;
use apex\app\sys\container;
use apex\app\msg\emailer;
use apex\app\exceptions\ApexException;
use GuzzleHttp\Psr7\UploadedFile;


/**
 * The central app / registry for Apex, controls the container, input arrays, 
 * registry, request and response contents, the response variables, redis 
 * connection, and all other centralized aspects of the application. 
 */
class app extends container
{

    /**
     * The various input arrays, including all $_POST, $_GET, $_COOKIE, and 
     * $_SERVER variables, which are sanitized and placed in these private arrays 
     * ensuring no outside code can modify their values.  Also has the $config 
     * array that holds all configuration variables. 
     */

    private static $config = [];
    private static $post = [];
    private static $get = [];
    private static $cookie = [];
    private static $server = [];
    private static $files = [];
    private static $request_body;

    /**
     * Variables regarding the HTTP request, such as request method, protocol, 
     * URI, the IP address, user agent, and others. 
     */

    private static $protocol;
    private static $host;
    private static $port;
    private static $method = 'GET';
    private static $content_type;
    private static $uri = 'index';
    private static $uri_original = 'index';
    private static $uri_segments = [];
    private static $uri_locked = false;
    private static $http_headers = [];
    private static $http_headers_keys = [];

    /**
     * Additional variables that contain some request information specific to 
     * Apex, such as the area being viewed, HTTP controller used to handle the 
     * request, etc. 
     */

    private static $area = 'public';
    private static $theme = 'koupon';
    private static $http_controller = 'http';
    private static $action = '';

    /**
     * User / location variables, such as the ID# of the authenticated user, the 
     * language, timezone and currency to display in, IP address, etc. 
     */

    private static $userid = 0;
    private static $recipient = 'public';
    private static $ip_address = '127.0.0.1';
    private static $user_agent;
    private static $language = 'en';
    private static $timezone = 'PST';
    private static $currency = 'USD';
    private static $verified_2fa = false;

    /**
     * Response variables, such as HTTP status code, content type, contents, etc. 
     */

    private static $res_status = 200;
    private static $res_content_type = 'text/html';
    private static $res_http_headers = [];
    private static $res_body;


    /**
     * Base application variables such as request type, container, services, and 
     * the app instance itself. 
     */
    private static $instance = null;
    private static $reqtype = 'http';
    private static $reqtype_original;
    private static $event_queue = [];

/**
 * Initialize the application. 
 *
 * initialize the app, create container, check and sanitize inputs, connect to 
 * redis, and all around get ready to handle the request. 
 *
 * @param string $reqtype The type of request, defaults to 'http' but can either be 'cli' or 'test'
 */
public function __construct(string $reqtype = 'http')
{ 

    // Return instance if already created
    if (self::$instance !== null) { 
        return self::$instance;
    }
    self::$instance = $this;

    // Initialize
    $this->initialize();

    // Build container
    $this->build_container($reqtype);

    // Load config
    self::$config = redis::singleton();

    // Unpack request
    if (self::$reqtype != 'cli') { 
        $this->unpack_request();
    }

    // Get client info
    $this->get_client_info();

}

/**
 * Get instance of the application.  Used to obtain instance for the 
 * dependency container statically within views. 
 */
public static function get_instance() { return self::$instance; }

/**
 * Private.  Conduct base initialization of application. 
 *
 * Complete base initialization of the app, such as loading redis connection 
 * info, set error / exception handlers, connect to redis, etc. 
 */
private function initialize()
{ 

    // Define the site path (root of Apex install)
    if (!defined('SITE_PATH')) { 
        define('SITE_PATH', realpath(__DIR__ . '/../'));
    }

    // Load config, constants and global functions
    require_once(SITE_PATH . '/etc/config.php');
    require_once(SITE_PATH . '/etc/constants.php');
    require_once(SITE_PATH . '/src/app/sys/functions.php');

    // Set time zone
    date_default_timezone_set('UTC');

    // Set error reporting
    error_reporting(E_ALL);
    set_exception_handler('handle_exception');
    set_error_handler('\error');

    // Set INI variables
    ini_set('pcre.backtrack_limit', '4M');
    ini_set('zlib.output_compression_level', '2');

    // Check if installed
    if (!defined('REDIS_HOST')) { 

        // Build container
        self::$instance = null;

        // Run installer
        $installer = new \apex\app\sys\installer();
        $installer->run_wizard();
    }

}

/**
 * Private.  Unpack the HTTP request. 
 *
 * Unpack the request.  Sanitizes the input arrays, checks which controller 
 * and URI is being accessed, etc. 
 */
private function unpack_request()
{ 

    // Sanitize inputs
    self::$get = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
    self::$post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
    self::$cookie = filter_input_array(INPUT_COOKIE, FILTER_SANITIZE_STRING);
    self::$server = filter_input_array(INPUT_SERVER, FILTER_SANITIZE_STRING);

    // Go through http headers
    if (!$headers = getallheaders()) { $headers = array(); }
    foreach ($headers as $key => $value) { 
        $skey = strtolower($key);
        if (!isset(self::$http_headers[$skey])) { self::$http_headers[$skey] = array(); }

        self::$http_headers[$skey][] = $value;
        self::$http_headers_keys[$skey] = $key;
    }

    // Set request body
    if (php_sapi_name() != "cli") { 
        self::$request_body = file_get_contents('php://input');
    }

    // Get uploaded files
    $this->get_uploaded_files();

    // Get host
    if (isset(self::$server['HTTP_HOST'])) { $host = self::$server['HTTP_HOST']; }
    elseif (isset(self::$server['SERVER_NAME'])) { $host = self::$server['SERVER_NAME']; }
    else { $host = self::$server['SERVER_ADDR'] ?? ''; }

    // Set other variables
    if (isset(self::$server['SERVER_PROTOCOL']) && preg_match("/^HTTP\/(.+)$/i", self::$server['SERVER_PROTOCOL'], $match)) { self::$protocol = $match[1]; }
    self::$host = preg_replace("/^www\./", "", strtolower($host));
    self::$port = self::$server['SERVER_PORT'] ?? 0;
    self::$method = self::$server['REQUEST_METHOD'] ?? 'GET';
    self::$content_type = self::$server['CONTENT_TYPE'] ?? '';
    self::$action = self::$post['submit'] ?? '';

    // Get uri
    $uri = preg_replace("/\?(.*)$/", "", trim(strtolower(self::$server['REQUEST_URI'] ?? 'index'), '/'));
    if ($uri == '') { $uri = 'index'; }

    // Set the URI
    self::set_uri($uri);
    self::$uri_original = self::$uri;

}

/**
 * Get all the uploaded files. 
 *
 * Goes through all uploaded files, and places them within the $files property 
 * using the UploadedFileInterface interface (via guzzlehttp) as per PSR 
 * specifications. 
 */
private function get_uploaded_files()
{ 

    // Upload files
    foreach ($_FILES as $key => $vars) { 
        if (!isset($vars['tmp_name'])) { continue; }

        // Check for array of files
        if (is_array($vars['tmp_name'])) { 
            self::$files[$key] = array();

            $x=0;
            foreach ($vars['tmp_name'] as $tmp_name) { 
                self::$files[$key][] = new UploadedFile($tmp_name, $vars['size'][$x], $vars['error'][$x], $vars['name'][$x], $vars['type'][$x]);
                $x++;
            }

        // Single file
        } else { 
            $files[$key] = new UploadedFile($vars['tmp_name'], $vars['size'], $vars['error'], $vars['name'], $vars['type']);
        }
    }

}

/**
 * Get client information, IP address and user agent 
 */
private function get_client_info()
{ 

    // Get IP address
    if (isset($this->server['HTTP_X_FORWARDED_FOR'])) { 
        self::$ip_address = $this->server['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($this->server['REMOTE_ADDR'])) { 
        self::$ip_address = $this->server['REMOTE_ADDR'];
    }

    // Validiate IP address
    if (self::$ip_address != '' && !filter_var(self::$ip_address, FILTER_VALIDATE_IP)) { 
        throw new ApexException('alert', "Invalid request, malformed IP address: {1}", self::$ip_address);
    }

    // Get user agent
    $ua = self::$server['HTTP_USER_AGENT'] ?? '';
    self::$user_agent = filter_var($ua, FILTER_SANITIZE_STRING);

}

/**
 * Assign service to DI container and static property. 
 *
 * Assigns a static service, plus adds it to the http container.  This is used 
 * to help make various services such as the database connection and debugger 
 * more accessible, while still conforming to PSR standards regarding the HTTP 
 * containers. 
 *
 * @param string $service The service (db, debug, log, template, msg_direct, or msg_rpc)
 * @param $class_name The class name to create
 * @param array $params Optional params to pass to the class when "make" is called.
 */
public function assign_service(string $service)
{ 

    // Ensure service is defined
    if (!isset($this->services[$service])) { 
        throw new ApexException('error', tr("Invalid service trying to be assigned, {1}", $service));
    }

    // Set variables
    $def = $this->services[$service];
    $interface = $def['interface'] ?? $def['class'];
    $params = $def['params'] ?? [];

    // Make class
    $obj = $this->make($def['class'], $params);
    $this->set($interface, $obj);

// Assign singleton, if needed
    $singleton_class = "apex\\services\\" . str_replace("/", "\\", $service);
    if ($this->has($singleton_class)) { 
        $singleton_class::singleton($obj);
    }

}

/**
 * Setup a test request. 
 *
 * Sets the various necessary variables for a test request, such as URI, 
 * request method, POST / GET arrays, and others.
 *
 * @param string $uri The URI of the http request
 * @param string $method The method of request (ie. POST or GET).  Defaults to GET.
 * @param array $post All POST variables of the request.
 * @param array $get All GET variables of the request.
 * @param array $cookie All COOKIE variables of the request.
 *
 * @return string The resulting HTML code of the request. 
 */
public function setup_test(string $uri, string $method = 'GET', array $post = [], array $get = [], array $cookie = [])
{ 

    // Set URI
    self::$uri_locked = false;
    self::set_uri($uri);
    self::$reqtype = 'test';

    // Set other input variables
    self::$method = $method;
    self::$post = $post;
    self::$get = $get;
    //self::$cookie = $cookie;
    self::$action = self::$post['submit'] ?? '';
    self::$verified_2fa = false;

    // Reset needed objects
    view::reset();
    //self::call([emailer::class, 'clear_queue']);

}

/**
 * Gets timezone offset and if is_dst. 
 *
 * @param string $timezone The ISO timezone to obtain (eg. PST, MST, EST, etc.)
 *
 * @return array The number of offset minutes, and a 1/0 if it's DST
 */
public static function get_tzdata(string $timezone = '')
{ 

    // Check for no redis
    if (!defined('REDIS_HOST')) { return array(0, 0); }
    if ($timezone == '') { $timezone = self::$timezone; }

    // Get timezone from db
    if (!$value = redis::hget('std:timezone', $timezone)) { 
        return array(0, 0);
    }
    $vars = explode("::", $value);

    // Return
    return array($vars[1], $vars[2]);

}

/**
 * Get currency formatting info. 
 *
 * Gets formatting info on a given currency, such as the currency sign, number 
 * of decimal points, etc. 
 *
 * @param string $currency The 3 character ISO code of the currency to retrieve.
 *
 * @return array An array of info regarding the currency
 */
public static function get_currency_data(string $currency):array
{ 

    // Get currency data
    if (!$data = redis::hget('std:currency', $currency)) { 

        // Check for crypto
        if (!redis::sismember('config:crypto_currency', $currency)) { 
            throw new ApexException('critical', "Currency does not exist in database, {1}", $currency);
        }

        // Return
        $vars = array(
            'symbol' => '',
            'decimals' => 8,
            'is_crypto' => 1
        );
        return $vars;
    }
    $line = explode("::", $data);

    // Set vars
    $vars = array(
        'symbol' => $line[1],
        'decimals' => $line[2],
        'is_crypto' => 0
    );

    // Return
    return $vars;

}

/**
 * Update a configuration variable 
 *
 * @param string $var The variable name within self::$config to update.
 * @param string $value THe value to update the configuration variable to.
 */
public static function update_config_var(string $var, $value)
{ 

if ($var == 'bitcoin_company_userid') { 
    $t = debug_backtrace();
    print_r($t[0]); exit;
}
    // Debug
    debug::add(5, tr("Updating configuration variable {1} to value: {2}", $var, $value));

    // Check format
    if (!preg_match("/^(.+?):(.+)$/", $var, $match)) { 
        throw new ApexException('error', tr("Unable to update configuration variable '{1}', as it is not in the correct format of PACKAGE:ALIAS", $var));
    }

    redis::hset('config', $var, $value);
    self::$config[$var] = $value;

    // Update mySQL
    list($package, $alias) = explode(":", $var, 2);
    db::query("UPDATE internal_components SET value = %s WHERE package = %s AND alias = %s", $value, $package, $alias);

}

/**
 * Verify a 2FA request 
 *
 * @param array $vars The full vars of the 2FA request from redis.
 */
public static function verify_2fa(array $vars)
{ 

    // Set variables
    self::$userid = (int) $vars['userid'];
    self::$http_controller = $vars['http_controller'];
    self::$area = $vars['area'];
    self::$theme = $vars['theme'];
    self::$uri = $vars['uri'];
    self::$method = $vars['request_method'];
    self::$get = $vars['get'];
    self::$post = $vars['post'];
    self::$verified_2fa = true;

    // Handle request
    self::call(["apex\\core\\controller\\http_requests\\" . $vars['http_controller'], 'process']);

}

/**
 * Check if verified via 2FA
 *
 * @return bool Whether or not the user has been verified via 2FA.
 */
public static function is_verified():bool { return self::$verified_2fa; }

/**
 * Increment a counter within the redis database 
 *
 * @param string $counter The name of the counter to increment.
 * @param int $increment The amount to increment by.  Defaults to 1.
 *
 * @return int The value of the counter.
 */
public static function get_counter(string $counter, int $increment = 1):int
{ 
    return redis::HINCRBy('counters', $counter, $increment);
}

/**
 * Get the request type
 *
 * @return string The request type
 */
public static function get_reqtype():string { return self::$reqtype; } 

/**
 * Set the request type 
 *
 * @param string $reqtype The request type to set
 */
public static function set_reqtype(string $reqtype) { self::$reqtype = $type; }

/**
 * Reset request type back to its original. 
 */
public function reset_reqtype() { self::$reqtype = self::$reqtype_original; }

/**
 * Set the area 
 *
 * @param string $area The area to set app to.
 */
public static function set_area(string $area)
{ 

    // Ensure valid area
    if (!is_dir(SITE_PATH . '/views/tpl/' . $area)) { 
        throw new apexException('error', tr("Invalid area specified, {1}", $area));
    }
    self::$area = $area;

    // Change theme as well
    if ($area == 'members') { 
        self::$theme = self::_config('users:theme_members');
    } else { 
        self::$theme = self::_config('core:theme_' . $area);
    }

    // Add to event queue, if inside worker
    if (self::$reqtype == 'worker') { 
        self::add_event('set_area', $area);
    } 

}

/**
 * Set the theme 
 *
 * @param string $theme The alias of the theme to set
 */
public static function set_theme(string $theme)
{ 

    // ENsure valid theme directory
    if (!is_dir(SITE_PATH . '/views/themes/' . $theme)) { 
        throw new ApexException('error', tr("Invalid theme specified, {1}", $theme));
    }
    self::$theme = $theme;

    // Add to event queue, if inside worker
    if (self::$reqtype == 'worker') { 
        self::add_event('set_theme', $theme);
    } 

}

/**
 * Change the theme of an area.
 *
 * @param string $area The alias of the area (eg. admin, members, public)
 * @param string $theme The alias of the theme to change to.
 */
public static function change_theme(string $area, string $theme)
{

    // ENsure valid theme directory
    if (!is_dir(SITE_PATH . '/views/themes/' . $theme)) { 
        throw new ApexException('error', tr("Invalid theme specified, {1}", $theme));
    }

    // Change theme
    if ($area == 'members') { 
        self::update_config_var('users:theme_members', $theme);
    } else { 
        self::update_config_var('core:theme_' . $area, $theme);
    }

    // Update /index to homepage layout, if available.
    if (file_exists(SITE_PATH . '/themes/views/' . $theme . '/layouts/homepage.tpl')) { 

        // Update, if needed
        if ($row = db::get_row("SELECT * FROM cms_pages WHERE area = 'public' AND filename = 'index'")) { 
            db::query("UPDATE cms_pages SET layout = 'homepage' WHERE id = %i", $row['id']);
        } else { 

            // Add to db
            db::insert('cms_pages', array(
                'area' => 'public',
                'layout' => 'homepage', 
                'title' => '', 
                'filename' => 'public/index')
            );

        }

        // Update redis
        redis::hset('cms:layouts', 'public/index', 'homepage');
    }

    // Return
    return true;

}


/**
 `* Set the URI 
 *
 * Sets the URI, which is also used by the template engine to display the 
 * correct template.  Only use this is you need to change the URI for some 
 * reason. 
 *
 * @param string $uri The URI to set and display next
 * @param bool $prepend_area Whether or not to prepend the area to the URI.  Used for system templates such as 2fa, 500, etc.
 * @param bool $lock_uri Whether or not to lock the URI from being changed future in the request.
 */
public static function set_uri(string $uri, bool $prepend_area = false, bool $lock_uri = false)
{ 

    // Return, if locked
    if (self::$uri_locked === true) { return; }

    // Prepend, if needed
    if ($prepend_area === true && self::$area != 'public') { 
        $uri = self::$area . '/' . $uri;
    }

    // Validate
    $uri = trim(str_replace(" ", "+", strtolower($uri)), '/');
    if (!filter_var('http://domain.com/' . trim($uri, '/'), FILTER_VALIDATE_URL)) { 
        throw new ApexException('error', "Invalid URI specified, {1}", $uri);
    }
    self::$uri_segments = explode('/', $uri);

    // Check for http controller
    if (file_exists(SITE_PATH . '/src/core/controller/http_requests/' . self::$uri_segments[0] . '.php')) { 
        self::$http_controller = array_shift(self::$uri_segments);
        if (count(self::$uri_segments) == 0) { 
            self::$uri_segments[] = 'index';
            $uri .= '/index';
        }
    } else { 
        self::$http_controller = 'http';
    }
    self::$uri = strtolower(filter_var(trim($uri, '/'), FILTER_SANITIZE_URL));
    self::$uri_locked = $lock_uri;

    // Add to event queue, if inside worker
    if (self::$reqtype == 'worker') { 
        self::add_event('set_uri', array(self::$uri, $lock_uri));
    } 


}

/**
 * Set the userid 
 *
 * @param int $userid The user ID# to set
 */
public static function set_userid(int $userid)
{ 
    self::$userid = $userid;
    self::$recipient = self::$area == 'admin' ? 'admin:' . $userid : 'user:' . $userid;

    // Add to event queue, if inside worker
    if (self::$reqtype == 'worker') { 
        self::add_event('set_userid', $userid);
    } 

}

/**
 * Set the session timezone 
 *
 * @param string $timezone The timezone to set session to
 */
public static function set_timezone(string $timezone) { self::$timezone = $timezone; }

/**
 * Set the session lanauge 
 *
 * @param string $language The two character language to set session to
 */
public static function set_language(string $language) { self::$language = $language; }

/**
 * Set the session currency 
 *
 * @param string $currency The three character ISO currency to set session to
 */
public static function set_currency(string $currency) { self::$currency = $currency; }

/**
 * Get the HTTP hostname being requested 
 *
 * @return string The http host being requested
 */
public static function get_host():string { return self::$host; }

/**
 * Get the port of the HTTP request 
 */
public static function get_port() { return self::$port; }

/**
 * Get HTTP protocol version of request 
 *
 * @return string The HTTP protocol version
 */
public static function get_protocol():string { return self::$protocol; }

/**
 * Get request method of HTTP request 
 *
 * @param string The request method (GET, POST, PUT, DELETE, etc.)
 */
public static function get_method():string { return self::$method; }

/**
 * Get the content type of the request 
 *
 * @return string Content type of the request
 */
public static function get_content_type():string { return self::$content_type; }

/**
 * Get the full URI being displayed 
 *
 * @return string The current URI
 */
public static function get_uri():string { return self::$uri; }

/**
 * Get URI segments 
 *
 * Returns array of the URI split by the / character with the first segment 
 * being left off if a valid HTTP controller.  For example, 
 * /admin/users/create with leave the /admin/ segment off as it's a HTTP 
 * controller, leaving the response as ['users', 'create']. 
 *
 * @return array The URI segments
 */
public static function get_uri_segments():array { return self::$uri_segments; }

/**
 * Get the original URI of the http request, in case it may have been modified 
 * during the processing. 
 *
 * @return string The original URI
 */
public static function get_uri_original():string { return self::$uri_original; }

/**
 * Get the request body 
 *
 * @return string The request body
 */
public static function get_request_body():string { return self::$request_body; }

/**
 * Get all request vars in an array instead of one-by-one. 
 *
 * @return array AN array of all app-specific request vars.
 */
public static function getall_request_vars()
{ 

    // Set vars
    $vars = array(
        'host' => self::$host,
        'port' => self::$port,
        'protocol' => self::$protocol,
        'method' => self::$method,
        'content_type' => self::$content_type,
        'uri' => self::$uri,
        'area' => self::$area,
        'theme' => self::$theme,
        'http_controller' => self::$http_controller,
        'action' => self::$action,
        'userid' => self::$userid,
        'language' => self::$language,
        'timezone' => self::$timezone,
        'currency' => self::$currency,
        'ip_address' => self::$ip_address,
        'user_agent' => self::$user_agent
    );

    // Return
    return $vars;

}

/**
 * Get the area 
 *
 * @return string The area
 */
public static function get_area():string { return self::$area; }

/**
 * Get the current theme. 
 *
 * @return string The current theme
 */
public static function get_theme():string { return self::$theme; }

/**
 * Get the HTTP controller (ie. middleware) being used for this request. 
 *
 * @return string The HTTP controller / middleware being used
 */
public static function get_http_controller():string { return self::$http_controller; }

/**
 * Get the action / value of 'submit' form field of previous request. Used to 
 * identify which form action to perform. 
 *
 * @return string Value of the 'submit' form field.
 */
public static function get_action():string { return self::$action; }

/**
 * Get the current user ID# 
 *
 * @return int The user ID
 */
public static function get_userid():int { return self::$userid; }

/**
 * Get the recipient.  Used for logs, etc.
 */
public static function get_recipient():string { return self::$recipient; }

/**
 * Get the IP address of requesting user 
 *
 * @return string The user's IP address
 */
public static function get_ip() { return self::$ip_address; }

/**
 * Get the user agent of the requestor 
 *
 * @return string The user agent requesting the page
 */
public static function get_user_agent() { return self::$user_agent; }

/**
 * Get the current timezone being used 
 *
 * @param string Timezone (eg. PST, MST, etc.)
 */
public static function get_timezone():string { return self::$timezone; }

/**
 * Get current language being used 
 *
 * @return string Two character ISO language (en, fr, de, etc.)
 */
public static function get_language():string { return self::$language; }

/**
 * Get current currency being used 
 *
 * @return string The currency ISO (USD, GBP, CNY, etc.)
 */
public static function get_currency():string { return self::$currency; }

/**
 * Get a $_POST variable 
 *
 * @param string $var The key of the variable to retrive.
 *
 * @return mixed The value of the variable, null if not exists.
 */
public static function _post(string $var) { return self::$post[$var] ?? null; }

/**
 * Get a $_GET variable
 * 
 * @param string $var The key of the variable to retrive.
 *
 * @return mixed The value of the variable, null if not exists.
 */
public static function _get(string $var) { return self::$get[$var] ?? null; }

/**
 * Get a $_COOKIE variable. 
 *
 * @param string $var The key of the variable to retrive.
 *
 * @return mixed The value of the variable, null if not exists.
 */
public static function _cookie(string $var) { return self::$cookie[$var] ?? null; }

/**
 * Get a $_SERVER variable
 *
 * @param string $var The key of the variable to retrive.
 *
 * @return mixed The value of the variable, null if not exists. 
 */
public static function _server(string $var) { return self::$server[$var] ?? null; }

/**
 * Get a http header
 *
 * @param string $key The key of the http header to retrive
 *
 * @return mixed The value of the variable, null if not exists. 
 */
public static function _header($key) { 
    $key = strtolower($key);
    return self::$http_headers[$key] ?? array();
}

/**
 * Get a http header as single comma delimited line
 *
 * @param string $key The key of the http header to retrive
 *
 * @return mixed The value of the variable, null if not exists. 
 */
public static function _header_line(string $key) { 
    $key = strtolower($key);
    return isset(self::$http_headers[$key]) ? implode(", ", self::$http_headers[$key]) : '';
}

/**
 * Get a configuration variable from the 'config' hash of redis 
 *
 * @param string $var The key of the variable to retrive.
 *
 * @return mixed The value of the variable, null if not exists.
 */
public static function _config(string $var) { return self::$config[$var] ?? null; }

/**
 * Check whether or not $_POST variable exists.
 *
 * @param string $var The key of the variable to check whether or not it exists.
 *
 * @return bool Whether or not the variable exists.
 */
public static function has_post(string $var) { return isset(self::$post[$var]) ? true : false; }

/**
 * Check whether or not a $_GET variable exists. 
 *
 * @param string $var The key of the variable to check whether or not it exists.
 *
 * @return bool Whether or not the variable exists.
 */
public static function has_get(string $var) { return isset(self::$get[$var]) ? true : false; }

/**
 * Check whether or not a $_COOKIE variable exists. 
 *
 * @param string $var The key of the variable to check whether or not it exists.
 *
 * @return bool Whether or not the variable exists.
 */
public static function has_cookie(string $var) { return isset(self::$cookie[$var]) ? true : false; }

/**
 * Check whether or not a $_SERVER variable exists
 *
 * @param string $var The key of the variable to check whether or not it exists.
 *
 * @return bool Whether or not the variable exists. 
 */
public static function has_server(string $var) { return isset(self::$server[$var]) ? true : false; }

/**
 * Check whether or not a configuration variable is defined. 
 *
 * @param string $var The key of the variable to check whether or not it exists.
 *
 * @return bool Whether or not the variable exists.
 */
public static function has_config(string $var) { return isset(self::$config[$var]) ? true : false; }

/**
 * Check whether or not a http header exists
 *
 * @param string $key The key of the http header to check
 *
 * @return bool Whether or not the variable exists. 
 */
public static function has_header($key) { 
    $key = strtolower($key);
    return isset(self::$http_headers[$key]) ? true : false;
}

/**
 * Get all variables within $_POST 
 */
public static function getall_post() { return is_array(self::$post) ? self::$post : array(); }

/**
 * Get all variables within $_GET 
 */
public static function getall_get() { return is_array(self::$get) ? self::$get : array(); }

/**
 * Get all variables within $_COOKIE 
 */
public static function getall_cookie() { return is_array(self::$cookie) ? self::$cookie : array(); }

/**
 * Get all variables within $_SERVER 
 */
public static function getall_server() { return is_array(self::$server) ? self::$server : array(); }

/**
 * Get all http headers 
 */
public static function getall_headers()
{ 

    $headers = array();
    foreach ($self::$http_headers_keys as $key => $orig) { 
        $headers[$orig] = self::$http_headers[$key];
    }
    return $headers;
}

/**
 * Get all variables within $_FILES 
 */
public static function getall_files():array { return self::$files; }

/**
 * Get all configuration variables 
 */
public static function getall_config() { return self::$config; }

/**
 * Clear all $_POST variables.  Useful to ensure HTML form is not pre-filled. 
 */
public static function clear_post() { 
    self::$post = array(); 
    self::$action = '';
}

/**
 * Clear all $_GET variables.  Useful to ensure HTML form is not pre-filled. 
 */
public static function clear_get() { self::$get = array(); }

/**
 * Clear all $_COOKIE variables
 */
public static function clear_cookie() { self::$cookie = []; }

/**
 * Set a new cookie
 *
 * @param string $name The name of the cookie.
 * @param string $value The value of the cookie. 
 * @param int $expire Expiration date in seconds.
 * @param string $path The path of the cookie.
 */
public static function set_cookie(string $name, string $value, int $expire = 0, string $path = '/')
{ 

    // Add cookie
    self::$cookie[$name] = $value;

    // Add to event queue, if inside worker
    if (self::$reqtype == 'worker') { 
        self::add_event('set_cookie', array($name, $value, $expire, $path));
    } 

    // Return, if CLI
    if (php_sapi_name() == "cli") { return true; }

    // Set cookie in browser
    if (isset($_COOKIE[$name])) { unset($_COOKIE[$name]); }
    if (!setcookie($name, $value, $expire, $path)) { 
        return false;
    }

    // Return
    return true;

}

/**
 * Sets the response HTTP status code 
 *
 * @param int $code The HTTP status code to give as a response
 *
 * @return bool Whether or not the operation was successful.
 */
public static function set_res_http_status(int $code)
{ 

    self::$res_status = $code;

    // Add to event queue, if inside worker
    if (self::$reqtype == 'worker') { 
        self::add_event('set_res_http_status', $code);
    } 

    // Debug
    debug::add(1, tr("Changed HTTP response status to {1}", $code));

}

/**
 * Sets the content type of the response that will be given. Defaults to 
 * 'text/html'. 
 *
 * @param string $type the content type to set the response to.
 *
 * @return bool Whether or not the operation was successful.
 */
public static function set_res_content_type(string $type)
{ 

    self::$res_content_type = $type;

    // Add to event queue, if inside worker
    if (self::$reqtype == 'worker') { 
        self::add_event('set_res_content_type', $type);
    } 

    // Debug
    debug::add(1, tr("Set response content-type to {1}", $type));

}

/**
 * Set a response HTTP header 
 *
 * @param string $key The name / key of the HTTP header
 * @param string $value The value of the HTTP header
 */
public static function set_res_header($key, $value) { 

    // Set header
    self::$res_http_headers[$key] = $value; 

    // Add to event queue, if inside worker
    if (self::$reqtype == 'worker') { 
        self::add_event('set_res_header', array($key, $value));
    } 


}

/**
 * Set the contents of the response that will be given.  Should be used with 
 * every request. 
 *
 * @param string $body The content of the response that will be given.
 */
public static function set_res_body(string $body)
{ 

    // Set response content
    self::$res_body = $body;

    // Debug
    debug::add(2, "Set response contents");

}

/**
 * Get the response HTTP sattus 
 *
 * @return int The response HTTP status
 */
public static function get_res_status() { return self::$res_status; }

/**
 * Get response content type. 
 *
 * @return string The response content-type
 */
public static function get_res_content_type() { return self::$res_content_type; }

/**
 * Get a single response HTTP header
 *
 * @param string $key The key of the http header to retrieve. 
 *
 * @return string The value of the single HTTP response header
 */
public static function get_res_header(string $key) { return self::$res_http_headers[$key] ?? ''; }

/**
 * Get list of all response HTTP headers 
 *
 * @return array Array of all response HTTP headers
 */
public static function get_res_all_headers() { return self::$res_http_headers; }

/**
 * Get the response body contents 
 *
 * @return string The response body
 */
public static function get_res_body() { return self::$res_body; }

/**
 * Outputs the response contents to the user, generally the web browser 
 */
public static function echo_response()
{ 

    // Debug
    debug::add(2, "Outputting response to web browser");

    // Finish debug session
    debug::finish_session();

    // Set HTTP status code
    http_response_code(self::$res_status);

    // Content type
    header("Content-type: " . self::$res_content_type);

    // Additional HTTP headers
    foreach (self::$res_http_headers as $name => $value) { 
        header($name . ': ' . $value);
    }

    // Echo response
    echo (string) self::$res_body;

}

/**
 * Stop execution, and display a template 
 *
 * Stops execution, and displays the provided template.  Useful if for 
 * example, to display the previous template with user errors. 
 *
 * @param string $uri The URI to display
 * @param bool $prepend_area If true, will prepend the area (eg. /admin/, /members/) to the URI.  Used for displaying system templates that reside in every area (500.tpl, 2fa.tpl, etc.).  Defaults to false.
 */
public static function echo_template(string $uri, bool $prepend_area = false)
{ 

    // Debug
    debug::add(1, tr("Forcing non-standard output of template: {1}", $uri));

    // Set route
    if ($prepend_area === true && self::$area != 'public') { 
        $uri = self::$area . '/' . $uri;
    }
    self::set_uri($uri);

    // Echo template
    self::set_res_body(view::parse());
    self::echo_response();

    // Finish session
    debug::finish_session();

    // Exit
    exit(0);

}

/**
 * Add event
 *
 * Used when request is being processed by an event listener.  Adds event 
 * that will change output of request to queue, which is then 
 * passed back to caller for processing.
 *
 * @param string $action The action being performed.
 * @param mixed $data Any data necessary for the action.
 */
public static function add_event(string $action, $data)
{

    $vars = array(
        'action' => $action, 
        'data' => $data
    );
    self::$event_queue[] = $vars;

}

/**
 * Get the event queue.
 *
 * @return array The event queue.
 */
public static function get_event_queue():array { return self::$event_queue; }




}

