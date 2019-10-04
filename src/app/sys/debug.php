<?php
declare(strict_types = 1);

namespace apex\app\sys;

use apex\app;
use apex\svc\redis;
use apex\svc\log;
use apex\svc\date;
use apex\app\interfaces\DebuggerInterface;


/**
 * Debugger
 * 
 * Service: apex\svc\debug
 *
 * Handles logging all debugger line items, and displaying the ( debug results 
 * within the browser when necessary. 
 *
 * This class is available within the services container, meaning its methods can be accessed statically via the 
 * service singleton as shown below.
 *
 * PHP Example
 * --------------------------------------------------
 * 
 * <?php
 *
 * namespace apex;
 *
 * use apex\app;
 * use apex\svc\debug;
 *
 * // Add debug entry
 * debug::add(2, "Something happened here", 'info');
 *
 */
class debug implements DebuggerInterface
{

    // properties
    private $log_dir;


    // Debugger properties
    private $date;
    protected $start_time = 0;
    protected $notes = array();
    protected $trace = array();
    protected $sql = array();
    public $data = array();


/**
 * Constructor 
 */
public function __construct()
{ 

    $this->start_time = time();
    $this->log_dir = SITE_PATH . '/storage/logs';

}

/**
 * Add entry to debug session 
 *
 * @param int $level Number beterrn 1 -53 defining the level of entry.
 * @param string $message The message to add
 * @param string $log_level Optional, and will add appropriate log item via logger if not debug.
 * @param int $is_system Defaults to 0, and used by internal error handlers to specify this as coming from PHP interpreter.
 */
public function add(int $level, string $message, $log_level = 'debug', $is_system = 0)
{ 

    // Check if DEBUG_LEVEL defined
    if (!app::_config('core:debug_level')) { return; }

    // Get caller file and line number
    $trace = debug_backtrace();
    $file = trim(str_replace(SITE_PATH, '', $trace[1]['file']), '/') ?? '';
    $line_number = $trace[1]['line'] ?? 0;

    // Add log
    if ($log_level != 'debug') { 
        log::add_system_log($log_level, $is_system, $file, $line_number, $message);
    }

    // Check debug level
    if ($level > app::_config('core:debug_level')) { return false; }
    if (app::_config('core:mode') != 'devel') { return false; }
    if (app::get_uri() == '500') { return false; }

    // Add entry to notes array
    $vars = array(
        'level' => $log_level,
        'note' => $message,
        'file' => trim(str_replace(SITE_PATH, '', $file), '/'),
        'line' => $line_number,
        'time' => time()
    );
    array_unshift($this->notes, $vars);

    // Add log
    if ($log_level == 'debug') { 
        log::add_system_log($log_level, 0, $file, $line_number, $message);
    }

}

/**
 * Add SQAL query 
 *
 * @param string $sql_query The SQL query that was executed
 */
public function add_sql(string $sql_query)
{ 
    array_unshift($this->sql, $sql_query);
}

/**
 * Finish debug session 
 *
 * Finish the session, compileall notes and data gatherered during request, 
 * and put them into redis for later display.  This is executed by the 
 * registry class at the end of each request. 
 */
public function finish_session()
{ 

    // Check if we're debugging
    if (app::_config('core:debug') < 1 && app::_config('core:mode') != 'devel') { 
        return;
    }
    if (app::get_http_controller() == 'admin' && app::get_uri() == 'admin/devkit/debugger') { return; }

    // Set data array
    $data = array(
        'date' => date::get_logdate(),
        'start_time' => $this->start_time,
        'end_time' => time(),
        'registry' => array(
            'request_method' => app::get_method(),
            'service' => 'http',
            'http_controller' => app::get_http_controller(),
            'uri' => app::get_uri(),
            'ip_address' => app::get_ip(),
            'user_agent' => app::get_user_agent(),
            'userid' => app::get_userid(),
            'timezone' => app::get_timezone(),
            'language' => app::get_language(),
            'currency' => app::get_currency(),
            'area' => app::get_area(),
            'action' => app::get_action()
        ),
        'post' => app::getall_post(),
        'get' => app::getall_get(),
        'cookie' => app::getall_cookie(),
        'server' => app::getall_server(),
        'sql' => $this->sql,
        'backtrace' => $this->get_backtrace(),
        'notes' => $this->notes
    );
    $this->data = $data;

    // Return if we're not saving
    if (app::_config('core:debug') < 1) { return; }

    // Save json to redis
    redis::set('config:debug_log', json_encode($data));
    redis::expire('config:debug_log', 10800);

    // Save response output
    if (is_writeable($this->log_dir . '/response.txt')) { 
        file_put_contents($this->log_dir . '/response.txt', app::get_response());
    }

    // Update config, as needed
    if (app::_config('core:debug') != 2) { 
        app::update_config_var('core:debug', '0');
    }

}

/**
 * Go through and format the backtrace as necessary for the debug session. 
 *
 * @param array $stack Optional existing backtrace to format, otherwise gets the backtrace from PHP. 
 *
 * @return array The formatted backtrace
 */
public function get_backtrace(array $stack = array()):array
{ 

    // Get back trace
    if (count($this->trace) > 0) { 
        return $this->trace;

    } elseif (count($stack) == 0) { 
        $stack = debug_backtrace(0);
        array_splice($stack, 0, 2);
    }

    // Go through stack
    $trace = array();
    foreach ($stack as $vars) { 
        $vars['file'] = isset($vars['file']) ? trim(str_replace(SITE_PATH, '', $vars['file']), '/') : '';
        if (!isset($vars['line'])) { $vars['line'] = ''; }
        if (isset($vars['args'])) { unset($vars['args']); }
        if (!isset($vars['class'])) { $vars['class'] = ''; }
        array_push($trace, $vars);
    }
    $this->trace = $trace;

    // Return
    return $trace;

}

/**
 * Get the data array / property
 *
 * @return array The data of the debugger session.
 */
public function get_data() { return $this->data; }



}

