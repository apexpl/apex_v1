<?php
declare(strict_types = 1);

namespace apex\core\test;

use apex\app;
use apex\app\sys\network;
use apex\app\pkg\package;
use apex\app\tests\test;


/**
 * Unit ets for the /lib/network.php class, which handles general repo 
 * communication such as list all packages / theme, check for upgrades, search 
 * packages, etc. 
 */
class test_network extends test
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
    $this->app = $app;

}

/**
 * tearDown 
 */
public function tearDown():void
{ 

}

/**
 * List all packages within repository 
 */
public function test_list_packages()
{ 

    // Get packages
    $client = $this->app->make(network::class);
    $packages = $client->list_packages();

    // Check type
    $this->assertisarray($packages, "Response from network::list_packages is not an array");

    // Go through packages
    $aliases = array();
    foreach ($packages as $vars) { 
        $this->assertisarray($vars, "An element from network::list_packages response is not an array");
        $this->assertarrayhaskey('alias', $vars, "An element from network::list_packages response does not have an 'alias' key");
        $aliases[] = $vars['alias'];
    }

    // Ensure users, transaction and support packages exist
    $this->assertcontains('users', $aliases, "The network::list_packages did not resopnse with the 'users' package");
    $this->assertcontains('transaction', $aliases, "The network::list_packages did not response with the 'transaction' package");
    $this->assertcontains('support', $aliases, "The network::list_packages did not resopnse with the 'support' package");

}

/**
 * Check to ensure specific packages existing within repos 
 */
public function test_check_package()
{ 

    // Go through packages
    $packages = array('users', 'transaction', 'support', 'devkit', 'digitalocean', 'bitcoin');
    foreach ($packages as $alias) { 

        // Check package
        $client = $this->app->make(network::class);
        $repos = $client->check_package($alias);

        // Test results
        $this->assertgreaterthan(0, count($repos), "Unable to find repo via check_package for package $alias");
    }

    // Check non-existent package
    $client = $this->app->make(network::class);
    $repos = $client->check_package('doesnotexistsno');
    $this->assertequals(0, count($repos), "The check_package function returned repos for a non-existent package");

}

/**
 * Search 
 */
public function test_search()
{ 

    // Search for users package
    $client = $this->app->make(network::class);
    $response = $client->search('user');
    $this->assertStringContains($response, "User Management");

    // Search for devkit
    $response = $client->search('development');
    $this->assertStringContains($response, 'devkit');

    // No package test
    $response = $client->search('djgsdjweiaklsdjdfsdgs');
    $this->assertStringContains($response, 'No packages found');

}


}

