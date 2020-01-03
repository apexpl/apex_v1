<?php
declare(strict_types = 1);

namespace tests\core;

use apex\app;
use apex\libc\db;
use apex\libc\redis;
use apex\libc\debug;
use apex\libc\io;
use apex\app\pkg\package;
use apex\app\pkg\package_config;
use apex\app\pkg\pkg_component;
use apex\app\pkg\theme;
use apex\app\pkg\upgrade;
use apex\app\tests\test;


/**
 * Handles all tests within the /src/app/pkg/ directory that 
 * were left over from all previous unit tests due to code overage misses.
 */
class test_pkg extends test
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
 * tearDown
 */
public function tearDown():void
{

}

/**
 * Log - invalid package alias
 */
public function test_load_invalid_package()
{

    $this->waitException('configuration file does not exist');
    $client = new package_config('some_junk_alias');
    $client->load();

}

/**
 * Invalid package alias
 */
public function test_package_validate_alias_invalid()
{

    $client = app::make(package::class);
    $ok = $client->validate_alias('some_val did3@_y et !_=+_will_never_exist_package');
    $this->assertFalse($ok);

}

/**
 * Package - Publish - alias not exists
 */
public function test_package_publish_not_exists()
{

    $this->waitException('The package does not exist');
    $client = app::make(package::class);
    $client->publish('some_junk_alias_that_will_never_exist');

}

/**
 * Package - Download - alias does not exist
 */
public function test_package_download_not_exists()
{

    $this->waitException('The package does not exist');
    $client = app::make(package::class);
    $client->download('some_junk_alias_that_will_never_exist');

}

/**
 * Package - Remove - invalid package alias
 */
public function test_package_remove_invalid_alias()
{

    $this->waitException('The package does not exist');
    $client = app::make(package::class);
    $client->remove('some_junk_alias_that_will_never_exist');

}

/**
 * Package - remove - core
 */
public function test_package_remove_core()
{

    $this->waitException('can not remove the core package');
    $client = app::make(package::class);
    $client->remove('core');

}

/**
 * Component - create - PHP file already exists
 */
public function test_component_create_php_file_exists()
{

    io::create_dir(SITE_PATH . '/src/unit_test/table');
    file_put_contents(SITE_PATH . '/src/unit_test/table/dupe_test.php', 'test');
    $this->waitException('PHP file already exists');
    pkg_component::create('table', 'unit_test:dupe_test', 'unit_test');

}

/**
 * Component - create view - invalid URI
 */
public function test_component_create_view_invalid_uri()
{

    $this->waitException('Invalid template URI');
    pkg_component::create('view', 'some@+d "die', 'unit_test');

}

/**
 * Component - create - invalid comp alias
 */
public function test_component_create_invalid_comp_alias()
{

    $this->waitException('Invalid component alias');
    pkg_component::create('table', 'some_junk_alias', 'unit_test');

}

/**
 * Component - create - no parent
 */
public function test_create_component_no_parent()
{

    $this->waitException('Unable to create component');
    pkg_component::create('tabpage', 'core:tester', 'unit_test');

}

/**
 * Component - create - parent not exists
 */
public function test_component_create_parent_not_exists()
{

    $this->waitException('parent does not exist');
    pkg_component::create('adapter', 'core:junk_alias:unit_test', 'unit_test');

}

/**
 * Component - create - no owner
 */
public function test_component_create_no_owner()
{

    $this->waitException('no owner package was specified');
    pkg_component::create('adapter', 'core:http_requests:utest', '');

}

/**
 * Component - create worker - no routing key
 */
public function test_component_create_worker_no_routing_key()
{

    $this->waitException('no routing key was defined');
    pkg_component::create('worker', 'unit_test:nokey', '');

}

/**
 * Component - delete - not exists
 */
public function test_component_delete_not_exists()
{

    $ok = pkg_component::remove('table', 'unit_test:some_junk_alias');
    $this->assertTrue(true);

}

/**
 * Upgrade - create - package not exists
 */
public function test_upgrade_create_package_not_exists()
{

    $this->waitException('does not exist');
    $client = app::make(upgrade::class);
    $client->create('some_junk_package_alias');

}

/**
 * Upgrade - create file hash - not exists
 */
public function test_upgrade_create_file_hash_not_exists()
{

    $this->waitException('No upgrade exists');
    $client = app::make(upgrade::class);
    $client->create_file_hash(8385372);

}

/**
 * Upgrade - compile - not exists
 */
public function test_upgrade_compile_not_exists()
{

    $this->waitException('No upgrade exists');
    $client = app::make(upgrade::class);
    $client->compile(93285276);

}

/**
 * upgrade - compile - not_open
 */
public function test_upgrade_compile_not_open()
{

    if ($upgrade_id = db::get_field("SELECT id FROM internal_upgrades WHERE status = 'closed' ORDER BY RAND() LIMIT 1")) { 
        $this->waitException('This upgrade is not open');
        $client = app::make(upgrade::class);
        $client->compile((int) $upgrade_id);
    } else { 
        $this->assertTrue(true);
    }

}

/**
 * upgrade - publish - not exists
 */
public function test_upgrade_publish_not_exists()
{

    $this->waitException('No upgrade exists');
    $client = app::make(upgrade::class);
    $client->publish(93827526);

}

/**
 * Upgrade - install - package not exists
 */
public function test_upgrade_install_package_not_exists()
{

    $this->waitException('The package does not exist');
    $client = app::make(upgrade::class);
    $client->install('some_junk_alias-that_will_never_exist');

}

/**
 * Theme - create - no alias
 */
public function test_theme_create_no_alias()
{

    $this->waitException('specified an invalid alias');
    $client = app::make(theme::class);
    $client->create('', 2);

}

/**
 * Theme - create - already exists
 */
public function test_theme_create_already_exists()
{

    $this->waitException('The theme already exists');
    $client = app::make(theme::class);
    $client->create('koupon', 2);

}

/**
 * Theme - public - not exists
 */ 
public function test_theme_public_not_exists()
{

    $this->waitException('No theme exists');
    $client = app::make(theme::class);
    $client->publish('some_junk_theme_alias_that_will_never_exist');

}

/**
 * Theme - remove - not exists
 */
public function test_theme_remove_not_exists()
{

    $this->waitException('No theme exists');
    $client = app::make(theme::class);
    $client->remove('some_junk_alias_that_will_never_exist');

}

}


