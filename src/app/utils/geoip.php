<?php
declare(strict_types = 1);

namespace apex\app\utils;

use apex\app;
use apex\svc\debug;

/**
 * GeoIP library
 *
 * Service: apex\svc\geoip
 *
 * Small one method class that allows efficient geo lookups of IP addresses.
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
 * use apex\svc\geoip;
 *
 * // Lookup user's IP address
 * $ip = geoip::lookup();
 * print_r($ip);
 */
class geoip 
{


/**
 * GeoIP an address, and return the country, state / province, and city.  Uses 
 * MaxMin free goecitylite database. 
 *
 * @param string $ipaddr IP address to look up.  Defaults to self::$ip_address.
 */
public function lookup(string $ipaddr = ''):array
{ 

    // Load library file
    require_once(SITE_PATH . '/src/app/utils/maxmind/autoload.php');

    // Get IP address
    if ($ipaddr == '') { $ipaddr = app::get_ip(); }

    // Debug
    debug::add(2, tr("Performing GeoIP lookup of IP address: {1}", $ipaddr));

    // Load reader
    $reader = new \MaxMind\Db\Reader(SITE_PATH . '/src/app/utils/maxmind/GeoLite2-City.mmdb');
    $vars = $reader->get($ipaddr);

    // Set results
    $results = array(
        'city' => $vars['city']['names']['en'],
        'country' => $vars['country']['iso_code'],
        'country_name' => $vars['country']['names']['en']
    );

    // Get postal code
    if (isset($vars['postal']) && is_array($vars['postal']) && isset($vars['postal']['code'])) { $results['postal'] = $vars['postal']['code']; }
    else { $results['postal'] = ''; }

    if (isset($vars['subdivisions']) && is_array($vars['subdivisions'])) { $results['province'] = $vars['subdivisions'][0]['names']['en']; }
    else { $results['province'] = ''; }

    // Close reader
    $reader->close();
    // Return
    return $results;

}


}

