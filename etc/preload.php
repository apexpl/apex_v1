<?php

/**
 * This file is meant for pre-loading within PHP, and will load all necessary 
 * PHP files upon server boot to take advantage of pre-loading.
 *
 * To enable pre-loading within Apex, modify your php.ini file, search for the 
 * 'opcache.preload' directory, and change it to the location of this script.
 *     opcache.preload = /path/to/etc/preload.php;
 */


// Set directories
$dirs = [
    'vendor/evenement/evenement/src', 
    'vendor/guzzlehttp/psr7/src', 
    'vendor/league/flysystem/src', 
    'vendor/php-amqplib/php-amqplib/PhpAmqpLib', 
    'vendor/phpseclib/phpseclib/phpseclib', 
    'vendor/psr/http-message/src', 
    'vendor/ralouphie/getallheaders/src', 
    'vendor/react/cache/src', 
    'vendor/react/dns/src', 
    'vendor/react/event-loop/src', 
    'vendor/react/promise/src', 
    'vendor/react/promise-timer/src', 
    'vendor/react/socket/src', 
    'vendor/react/stream/src', 
    'vendor/symfony/http-foundation', 
    //'vendor/symfony/notifier', 
    'vendor/symfony/polyfill-ctype', 
    'vendor/symfony/polyfill-intl-idn', 
    'vendor/symfony/polyfill-mbstring', 
    'vendor/symfony/polyfill-php72', 
    //
    //'vendor/symfony/routing', 
    'src'
];

// Load autoload.php
require_once(__DIR__ . '/../vendor/autoload.php');

// Load some basic files
opcache_compile_file(__DIR__ . '/../public/index.php');
opcache_compile_file(__DIR__ . '/../bootstrap/http.php');
opcache_compile_file(__DIR__ . '/../bootstrap/cli.php');
opcache_compile_file(__DIR__ . '/../etc/config.php');
opcache_compile_file(__DIR__ . '/../etc/constants.php');

// Go through all directories
$total_loaded = 5;
foreach ($dirs as $dir) { 

    // Go through files
    $files = parse_dir(__DIR__ . '/../' . $dir);
    foreach ($files as $file) { 
        if (!preg_match("/\.php$/", $file)) { continue; }
        require_once(__DIR__ . '/../' . $dir . '/' . $file);
        $total_loaded++;
    }

}

// Go through all views
$views = parse_dir(__DIR__ . '/../views/php');
foreach ($views as $file) { 
    if (!preg_match("/\.php$/", $file)) { continue; }
    opcache_compile_file(__DIR__ . '/../views/php/' . $file);
    $total_loaded++;
}

// Echo message
echo "Successfully pre-loaded a total of $total_loaded files.\n";


/**
 * Parse a directory recursively, and return all files within.
 *
 * @param string $rootdir The directory to parse.
 *
 * @return array All files within the directory.
 */
function parse_dir(string $rootdir)
{ 

    // Set variables
    $search_dirs = array('');
    $results = array();

    // Go through directories
    while ($search_dirs) { 
        $dir = array_shift($search_dirs);
        if (preg_match("/(test|tests)/i", $dir)) { continue; }
        if (preg_match("/test/i", $dir)) { continue; }
        if (preg_match("/test/i", $dir)) { continue; }

        // Open, and search directory
        if (!$handle = opendir("$rootdir/$dir")) { 
            die("Unable to open directory, $rootdir/$dir");
        }
        while (false !== ($file = readdir($handle))) { 
            if ($file == '.' || $file == '..') { continue; }
            if (preg_match("/test/i", $file)) { continue; }

            // Parse file / directory
            if (is_dir("$rootdir/$dir/$file")) { 
                if (empty($dir)) { $search_dirs[] = $file; }
                else { $search_dirs[] = "$dir/$file"; }
            } else { 
                if (empty($dir)) { $results[] = $file; }
                else { $results[] = "$dir/$file"; }
            }
        }
        closedir($handle);
    }

    // Return
    return $results;

}



