<?php
declare(strict_types = 1);

namespace apex\core\cron;

use apex\app;


class server_check 
{




    // Properties
    public $time_interval = 'I5';
    public $name = 'Server Health Check';

    /**
     * Processes the crontab job. 
     */




public function process()
{ 

    // Initialize
    $results = array();

    // Get CPU
    $lines = explode("\n", shell_exec("/usr/bin/top -n 1"));
    if (preg_match("/load average: (.+?)\(/", $lines[0], $match)) { 
        $results['cpu'] = sprintf("%.2f", (array_sum(explode(", ", $match[1])) / 3));
    } else { $results['cpu'] = 0; }

    // Get memory
    $lines = explode("\n", shell_exec('top -n 1 -b | grep "Mem"'));
    if (preg_match("/(\d+?) total(.+?)(\d+?) used/", $lines[0], $match)) { 
        $total = $match[1];
        $used = $match[3];
        $results['ram'] = (sprintf("%.2f", ($used * 100) / $total));
    } else { $results['ram'] = 0; }

    // Set HD variables
    $results['hd'] = array();

        // Go through HD partitions
    $lines = explode("\n", shell_exec("df"));
    array_shift($lines);
    foreach ($lines as $line) { 
        $vars = preg_split("/(\s+)/", $line);
        if (!isset($vars[5])) { continue; }
        $results['hd'][$vars[5]] = str_replace("%", "", $vars[4]);
    }

    // Update config var
    app::update_config_var('core:server_status', json_encode($results));




}


}

