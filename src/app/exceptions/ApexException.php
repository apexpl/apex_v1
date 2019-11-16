<?php
declare(strict_types = 1);

namespace apex\app\exceptions;

use apex\app;
use apex\svc\log;
use apex\svc\debug;
use apex\svc\date;
use apex\svc\view;


/**
 * The general / main Apex exception.  Handles the logging and rendering of 
 * errors for all Apex based exceptions. 
 */
class ApexException   extends \Exception
{

    // Properties
    protected $log_level;
    protected $message;
    protected $is_system = 0;
    protected $is_generic = 0;
    protected $sql_query;

/**
 * Construct 
 *
 * @param string $log_level The log level of the exception (eg. error, alert, critical, notice, etc.)
 * @param string $message The actual message
 * @param array $vars Variables to replace with the placeholders in the message.
 */
public function __construct(string $log_level, string $message, ...$vars)
{ 

    // Get log level
    if (preg_match("/^system:(.+)$/", $log_level, $match)) { 
        $this->is_system = 1;
        $this->log_level = $match[1];
    } else { 
        $this->log_level = $log_level;
    }

    // FOrmat message
    $this->code = 500;
    $this->message = tr($message, ...$vars);

}

/**
 * Process the exception, log and display it as necessary 
 */
public function process()
{ 

    // Get trace
    $trace = $this->getTrace();

    // Get file / line number, if system
    if ($this->is_system == 1 && isset($trace[0]) && isset($trace[0]['file'])) { 
        $this->file = $trace[0]['file'];
        $this->line = $trace[0]['line'];
        array_shift($trace);
    }
    $this->file = str_replace(SITE_PATH , '', $this->file);

    // Add log of message, as needed
    $this->report();

    // Return if not displaying error
    if (app::_config('core:mode') == 'prod' && in_array($this->log_level, array('warning', 'notice', 'info'))) { 
        //return true;
    }

    // Finish debug session
    debug::get_backtrace($trace);
    debug::finish_session();

    // Display the error
    $this->render();

}

/**
 * Report, and add necessary logging 
 */
protected function report()
{ 

    // Add debug log, which also adds to general logger
    debug::add(1, $this->message, $this->log_level, $this->is_system);

    // Add SQL error, if needed
    if ($this instanceof DBException) { 
        $line = '[' . date::get_logdate() . '] ' . $this->sql_query;
        file_put_contents(SITE_PATH . '/storage/logs/sql_error.log', "$line\n", FILE_APPEND);
    }

}

/**
 * Display the error as necessary, depending on the origin of request (CLI, 
 * web browser, AJAX, etc.) 
 */
protected function render()
{ 

    // Check FOR CLI usage
    if (php_sapi_name() == "cli") { 
        $this->render_cli();

    // JSON error
    } elseif (preg_match("/\/json$/", app::get_res_content_type())) { 
        $this->render_json();

    }

    // Check for .tpl file
    $tpl_file = $this->is_generic == 1 && app::_config('core:mode') != 'devel' ? '500_generic' : '500';
    if (!file_exists(SITE_PATH . '/views/tpl/' . app::get_area() . '/' . $tpl_file . '.tpl')) { 
        $this->render_notpl();
    }

    // Set registry properties
    app::set_res_http_status(500);
    app::set_uri($tpl_file, true);

    // Template variables
    view::assign('err_message', $this->message);
    view::assign('err_file', $this->file);
    view::assign('err_line', $this->line);

    // Parse template
    app::set_res_body(view::parse());
    app::Echo_response();

    // Exit
    exit(0);

}

/**
 * Render CLI 
 */
protected function render_cli()
{ 

    // Echo error message
    echo "ERROR: $this->message\n\n";
    echo "File: $this->file\n";
    echo "Line: $this->line\n\n";

    // Exit
    exit(0);

}

/**
 * Render JSON error 
 */
protected function render_json()
{ 

    // Set vars
    $vars = array(
        'status' => 'error',
        'errmsg' => $this->message,
        'errfile' => $this->file,
        'errline' => $this->line
    );

    // Echo
echo json_encode($vars);
    exit(0);

}

/**
 * Render with no .tpl file found 
 */
protected function render_notpl()
{ 

    echo "We're sorry, but an error occurred and no error .tpl template exists on the server.<br /><br />\n\n";
    if (app::_config('core:mode') == 'devel') { 
        echo $this->message . "<br /><br />\n\n";
        echo "File: " . $this->file . "<br />\n";
        echo "Line: " . $this->line . "<br />\n";
    }

    // Exit
    exit(0);

}


}

