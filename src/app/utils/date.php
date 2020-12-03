<?php
declare(strict_types = 1);

namespace apex\app\utils;

use apex\app;
use apex\libc\db;
use apex\app\utils\hashes;
use apex\app\exceptions\ApexException;


/**
 * Date Library
 *
 * Service: apex\libc\date
 *
 * Handles various date functions, such ad adding / subtracting intervals from 
 * dates, getting the log date, etc. 
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
 * use apex\libc\date;
 *
 * // Add interval
 * $new_date = date::add_interval('M1');
 *
 */
class date
{

    // Properties
    // Names
    private $names = array(
        'I' => 'Minute', 
        'H' => 'Hour', 
        'D' => 'Day', 
        'W' => 'Week', 
        'M' => 'Month', 
        'Y' => 'Year'
    );

/**
 * Get log date 
 *
 * Get date for log files.  This ensures the date is formatted to 
 * DEFAULT_TIMEZONE, instead of UTC or the authenticated user's timezone. 
 */
public function get_logdate():string
{ 

    // Get timezone data
    $timezone = app::_config('core:default_timezone') ?? 'PST';
    list($offset, $dst) = app::get_tzdata($timezone);
    $offset *= 60;

    // Get log date
    $secs = $offset < 0 ? (time() - $offset) : (time() + $offset);
    $logdate = date('Y-m-d H:i:s', $secs);

    // Return
    return $logdate;

}

/**
 * Add interval to date. 
 *
 * @param string $interval The time interval to add formateed in standard Apex format (eg. M1 = 1 month, I30 = 30 minutes, 6H = 6 hours)
 * @param string $from_date The date to add the interval to, defaults to now().  Can be either time in seconds since epoch, or standard timestamp format (YYYY-MM-DD HH:II:SS)
 * @param bool $return_datestamp If set to false, returns the seconds from epoch, and otherwise returns timestamp (YYYY-MM-DD HH:II:SS)
 *
 * @return string The new date.
 */
public function add_interval(string $interval, $from_date = '', $return_datestamp = true)
{ 

    // Parse interval
    if (!preg_match("/^(\w)(\d+)$/", $interval, $match)) { 
        throw new ApexException('error', "Invalid date interval specified, {1}", $interval);
    }
    if ($from_date == '') { $from_date = 0; }

    // Get start date / time
    if (!preg_match("/^(\d\d\d\d)-(\d\d)-(\d\d) (\d\d):(\d\d):(\d\d)$/", (string) $from_date, $d)) { 
        $secs = $from_date == 0 ? time() : $from_date;
        $from_date = date('Y-m-d H:i:s', (int) $secs);
    }

    // Get date
    return db::add_time($this->names[$match[1]], (int) $match[2], $from_date, $return_datestamp);

}

/**
 * Subtract interval from date. 
 *
 * @param string $interval The time interval to add formateed in standard Apex format (eg. M1 = 1 month, I30 = 30 minutes, 6H = 6 hours)
 * @param string $from_date The date to add the interval to, defaults to now().  Can be either time in seconds since epoch, or standard timestamp format (YYYY-MM-DD HH:II:SS)
 * @param bool $return_datestamp If set to false, returns the seconds from epoch, and otherwise returns timestamp (YYYY-MM-DD HH:II:SS)
 *
 * @return string The new date.
 */
public function subtract_interval(string $interval, $from_date = '', $return_datestamp = true)
{ 

    // Parse interval
    if (!preg_match("/^(\w)(\d+)$/", $interval, $match)) { 
        throw new ApexException('error', "Invalid date interval specified, {1}", $interval);
    }
    if ($from_date == '') { $from_date = 0; }

        // Get start date / time
    if (!preg_match("/^(\d\d\d\d)-(\d\d)-(\d\d) (\d\d):(\d\d):(\d\d)$/", (string) $from_date, $d)) { 
        $secs = $from_date == 0 ? time() : $from_date;
        $from_date = date('Y-m-d H:i:s', (int) $secs);
    }

    // Get and return new date
    return db::subtract_time($this->names[$match[1]], (int) $match[2], $from_date, $return_datestamp);

}

/**
 * Get last seen display time. 
 *
 * @param int $secs The number of seconds, epoch UNIX time from the PHP time() stamp.
 */
public function last_seen($secs)
{ 

    // Initialize
    $seen = 'Unknown';
    $orig_secs = $secs;
    $secs = (time() - $secs);

    // Check last seen
    if ($secs < 20) { $seen = 'Just Now'; }
    elseif ($secs < 60) { $seen = $secs . ' secs ago'; }
    elseif ($secs < 3600) { 
        $mins = floor($secs / 60);
        $secs -= ($mins * 60);
        $seen = $mins . ' mins ' . $secs . ' secs ago';
    } elseif ($secs < 86400) { 
        $hours = floor($secs / 3600);
        $secs -= ($hours * 3600);
        $mins = floor($secs / 60);
        $seen = $hours . ' hours ' . $mins . ' mins ago';
    } else { 
        $seen = date('D M dS H:i', $orig_secs);
    }

    // Return
    return $seen;

}

/**
 * Parse date interval into readable format
 *
 * @param string $interval The date interval
 *
 * @return string The formatted string
 */
public function parse_date_interval(string $interval)
{

    // Check
    if (!preg_match("/^(\w)(\d+)$/", $interval, $match)) { 
        return '';
    }
    if (!isset($this->names[$match[1]])) { return ''; }

    // Get name
    $name = $match[2] . ' ' . $this->names[$match[1]];
    if ($match[2] > 1) { $name .= 's'; }

    // Return
    return $name;

}

}

