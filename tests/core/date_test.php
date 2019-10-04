<?php
declare(strict_types = 1);

namespace tests\core;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\app\utils\date;
use apex\app\tests\test;


/**
* Handful of unit tests for the date library located 
 * at /src/app/utils/date.php
 */
class test_date extends test
{

/**
 * setUp
 */
public function setUp():void
{

    // Get app
    if (!$app = app::get_instance()) { 
        $app = new app('test');
    }

}

/**
 * Test timezone data
 */
public function test_tzdata()
{

    // EST
    list($offset, $dst) = app::get_tzdata('PST');
    $this->assertEquals(-480, (int) $offset, "PST timezone offset is not 480");

    // MSK
list($offset, $dst) = app::get_tzdata('MSK');
    $this->assertEquals(180, (int) $offset, "MSK timezone offset is not 180");

}

/**
 * Test get_logout()
 */
public function test_get_logdate()
{

    $client = new date();

    // Check date
    $date = $client->get_logdate();
    $this->check_date($date);

}

/**
 * Test add_interval()
 *     @dataProvider provider_interval
 */
public function test_add_interval($interval, $secs)
{
// Initialize
    $from_secs = 1562185323;
    $client = new date();

    // Add interval
    $chk_secs = $client->add_interval($interval, $from_secs, false);
    $chk_date = $client->add_interval($interval, $from_secs, true);

    // Assert
    $this->assertEquals($chk_secs, ($from_secs + $secs), "Add interval didn't work for $interval");
    $this->check_date($chk_date);

}

/**
 * Test add_interval()
 *     @dataProvider provider_interval
 */
public function test_subtract_interval($interval, $secs)
{
// Initialize
    $from_secs = 1562185323;
    $client = new date();

    // Subtract interval
    $chk_secs = $client->subtract_interval($interval, $from_secs, false);
    $chk_date = $client->subtract_interval($interval, $from_secs, true);

    // Assert
    $this->assertEquals($chk_secs, ($from_secs - $secs), "Subtract interval didn't work for $interval");
    $this->check_date($chk_date);

}

/**
 * Provider for intervals
 */
public function provider_interval()
{

    // Set results
    $results = array(
        array('I30', 1800), 
        array('H9', 32400),  
        array('D1', 86400),  
        array('D4', 345600),  
        array('D17',1468800),  
        array('W1', 604800),  
        array('W3', 1814400),  
        array('M1', 2592000),  
        array('M3', 7776000)
    );

    // Return
    return $results;

}

/**
 * Test last_seen()
 */
public function test_last_seen()
{

    // Initialize
    $client = new date();

    // Assert
    $this->assertEquals('Just Now', $client->last_seen(time()), "Last seen for Just Now does not work");
    $this->assertEquals('36 secs ago', $client->last_seen(time() - 36), "Last seen for 36 secs ago does not work");
    $this->assertEquals('7 mins 51 secs ago', $client->last_seen(time() - 471), "Last seen for 7 mins 51 secs ago does not work");
    $this->assertEquals('3 hours 41 mins ago', $client->last_seen(time() - 13283), "Last seen for 3 hours 41 mins 23 secs ago does not work");

}


/**
 * Check a date format
 */
private function check_date($date)
{

    // Check
    if (!preg_match("/^(\d\d\d\d)-(\d\d)-(\d\d)\s(\d\d):(\d\d):(\d\d)$/", $date, $match)) { 
        $this->assertTrue(false, "The date is not formatted correctly, $date");
    } else { 
        $this->assertTrue(true);
    }

// Check year
    $ok = preg_match("/^201/", $match[1]) ? true : false;
    $this->assertTrue($ok, "Year is not correct in date, $date");

    // Check month
    $ok = ($match[2] >= 1 && $match[2] <= 12) ? true : false;
    $this->assertTrue($ok, "Month is not correct in date, $date");

    // Check date
    $ok = ($match[3] >= 1 && $match[3] <= 31) ? true : false;
    $this->assertTrue(true, "Day is not correct in date, $date");

    // Check hour
    $ok = ($match[4] >= 0 && $match[4] <= 23) ? true : false;
    $this->assertTrue(true, "Hour is not correct in date, $date");

    // Chec minute
    $ok = ($match[5] >= 0 && $match[5] <= 59) ? true : false;
    $this->assertTrue($ok, "Minute is not cirrect in date, $date");

    // Check second
    $ok = ($match[6] >= 0 && $match[6] <= 59) ? true : false;
    $this->assertTrue($ok, "Seconds is not cirrect in date, $date");

}

}



