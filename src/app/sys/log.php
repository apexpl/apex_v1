<?php
declare(strict_types = 1);

namespace apex\app\sys;

use apex\app;
use apex\svc\date;
use apex\svc\io;
use apex\app\interfaces\LoggerInterface;


/**
 * Log Handler
 *
 * Service: apex\svc\log
 *
 * Handles all logging and ebugging of Apex, and is fully PSR3 compliant. 
 * Supports the 7 log levels, log channels, and PSR compliant placeholders 
 * within the messages. 
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
 * use apex\svc\log;
 *
 */
class log implements LoggerInterface
{


    // Log level constants
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';

    // Log properties
    private $log_dir;
    private $log_levels = [];
    private $channel = 'apex';
    private $streams = [];

    // System log properties
    protected $log_file;
    protected $log_line;
    protected $is_system = 0;

/**
 * Initiates anew class, allowing for channel creation 
 *
 * @param string $channel_name The name of the log channel to create, defaults to 'apex'.
 */
public function __construct(string $channel_name = 'apex')
{ 

    // Set properties
    $this->channel = $channel_name;
    $this->log_dir = SITE_PATH . '/storage/logs';
    if (app::_config('core:log_level') === null) { return; }
    $this->log_levels = explode(",", app::_config('core:log_level'));

    // Add request log line
    $uri = '/' . (app::get_http_controller() == 'http' ? 'public' : app::get_http_controller()) . '/' . app::get_uri();
    $line = '[' . date::get_logdate() . '] (' . app::get_ip() . ') ' . app::get_method() . $uri;
    $this->write('access', $line);

}

/**
 * Set the log levels 
 *
 * @param array $levels The log levels to set.  Only levels within this array will be logged.
 */
public function set_log_levels(array $levels)
{ 
    $this->log_levels = $levels;
}

/**
 * DEBUG message.
 *
 * @param mixed $msg The log entry message.
 * @param array $context Values to replace the placeholders within the message with. 
 */
public function debug($msg, array $context = array())
{ 

    // Add log
    $this->log(static::DEBUG, $msg, $context);

}

/**
 * INFO message. 
 *
 * @param mixed $msg The log entry message.
 * @param array $context Values to replace the placeholders within the message with. 
 */
public function info($msg, array $context = array())
{ 

    // Add log
    $this->log(static::INFO, $msg, $context);

}

/**
 * WARNING message. 
 *
 * @param mixed $msg The log entry message.
 * @param array $vars    Values to replace the placeholders within the message with. 
 */
public function warning($msg, array $vars = array())
{ 

    // Add log
    $this->log('warning', $msg, $vars);

}

/**
 * NOTICE message.
 *
 * @param mixed $msg The log entry message.
 * @param array $vars Values to replace the placeholders within the message with.  
 */
public function notice($msg, array $vars = array())
{ 

    // Add log
    $this->log('notice', $msg, $vars);

}

/**
 * ERROR message.
 *
 * @param mixed $msg The log entry message.
 * @param array $vars Values to replace the placeholders within the message with.  
 */
public function error($msg, array $vars = array())
{ 

    // Add log
    $this->log('error', $msg, $vars);

}

/**
 * CRITICAL message.
 *
 * @param mixed $msg The log entry message.
 * @param array $vars Values to replace the placeholders within the message with.  
 */
public function critical($msg, array $vars = array())
{ 

    // Add log
    $this->log('critical', $msg, $vars);

}

/**
 * ALERT message
 *
 * @param mixed $msg The log entry message.
 * @param array $vars Values to replace the placeholders within the message with.  
 */
public function alert($msg, array $vars = array())
{ 

    // Add log
    $this->log('alert', $msg, $vars);

}

/**
 * EMERGENCY message.
 *
 * @param mixed $msg The log entry message.
 * @param array $vars Values to replace the placeholders within the message with.  
 */
public function emergency($msg, array $vars = array())
{ 

    // Add log
    $this->log('emergency', $msg, $vars);

}

/**
 * Adds new line to log file 
 *
 * @param string $level One of the eight PSR3 log levels
 * @param mixed $msg The log message
 * @param array $context Optional array of context variables
 */
public function log($level, $msg, array $context = array())
{ 

    // Check log level
    if (!in_array($level, array('debug','info','warning','notice','error','alert','emergency','critical'))) { 
        throw new \InvalidArgumentException("Level $level is not supported.  Use one of the supported levels: debug, info, warning, notice, error, alert, critical, emergency");

    // Check configuration, and skip log if needed
    } elseif ($level != 'debug' && !in_array($level, $this->log_levels)) { 
        return;
    }

    // Convert message to string, if needed
    if (is_object($msg) && !method_exists($msg, '__toString')) { 
        throw new \InvalidArgumentException("The message is an object, and does not have a __toString method");
    } elseif (is_object($msg)) { 
        $msg = $msg->__toString();
    }

    // Set variables for log line
    $file_var = $this->log_file != '' ? ' (' . $this->log_file . ':' . $this->log_line . ') ' : ' ';
    $logdate = '[' . date::get_logdate() . ' ] ';

    // Add to global messages logfile
    $msg_line = $logdate . $this->channel . '.' . strtoupper($level) . $file_var . tr($msg, $context) . ' ' . json_encode($context);
    $this->write('messages', $msg_line);

    // Add to level-specific logfile
    $type_line = $logdate . $this->channel . $file_var . tr($msg, $context) . ' ' . json_encode($context);
    $this->write($level, $type_line);

    // Add to system logfile, if needed
    if ($this->is_system == 1) { 
        $this->write('system', $msg_line);
    }

    // Blank variables
    $this->log_file = '';
    $this->log_line = '';
    $this->is_system = 0;

}

/**
 * Add system log 
 *
 * Adds a system log, and should never be executed manually.  This is used 
 * when PHP throws an error or the trigger_error() function is used, plus when 
 * a DEBUG level message comes in.  This is due to the fact file and line 
 * numbers are included, plus for filtering reasons. 
 *
 * @param string $type One of the eight PSR supported log levels
 * @param int $is_system Whether this message is from the PHP compiler or not.
 * @param $file The __FILE__ variable captured.
 * @param int $line The __LINE__ variable captured.
 * @param string $msg The log message.
 * @param array $vars Values to replace placeholders in the message with.
 */
public function add_system_log(string $type, int $is_system = 0, string $file, int $line, string $msg, ...$vars)
{ 

    // Set variables
    $this->log_file = trim(str_replace(SITE_PATH, "", $file), '/');
    $this->log_line = $line;
    $this->is_system = $is_system;

    // Add log
    $this->$type($msg, $vars);

}

/**
 * Write line to a log file 
 *
 * @param string $level the log level / filename
 * @param string $line The line to write
 */
protected function write(string $level, string $line)
{ 

    // Create directory, if needed
    if (!is_dir($this->log_dir)) { 
        io::create_dir($this->log_dir);
    }

    // Open stream, if needed
    if (!isset($this->streams[$level])) { 
        $logfile = $this->log_dir . '/' . $level . '.log';

        // Check if writeable
        if (file_exists($logfile) && !is_writeable($logfile)) { 
            return false;
        } elseif ((!file_exists($logfile)) && (!is_writeable($this->log_dir))) { 
            return false;
        }

        if (!$stream = fopen($logfile, 'a')) { 
            return false;
        }
        $this->streams[$level] = $stream;
    }

    // Add line to file
    fwrite($this->streams[$level], trim($line) . "\n");

}


}

