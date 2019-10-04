<?php
declare(strict_types = 1);

namespace tests\core;

use apex\app;
use apex\app\tests\test;
use apex\app\sys\components;

/**
 ( Various unit tests for the components.php library, 
 * which handles checking / loading of components.
 */
class test_components extends test
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

    // Initialize components class
    $this->components = $app->make(components::class);

}

/**
 * tearDown
 */
public function tearDown():void
{

}

/**
 * Check if components exist
 * 
 *     @dataProvider provider_check
 */
public function test_check($type, $alias, $expected)
{

    // Initialize
    $components = $this->components;

    // Check
    $ok = true;
    if (!list($package, $parent, $alias) = $components->check($type, $alias)) { 
        $ok = false;
    }

    // Check response
    if ($ok === $expected) { 
        $this->assertTrue(true);
    } else { 
        $not = $expected === true ? ' NOT ' : '';
        $this->assertTrue(false, "Component check does $not exist and result is supposed to be opposite, type: $type, alias: $alias");
    }

    // Load component
    if ($expected === true && $type != 'test') { 
        if (!$client = $components->load($type, $alias, $package, $parent)) { 
            $this->assertTrue(false, "Unable to load component, type: $type, package; $package, parent: $parent, alias: $alias");
        } else { 
            $this->assertTrue(true);
        }
    }

}

/**
 * Provider for test_check() test
 */
public function provider_check()
{

    // Set results
    $results = array(
        array('worker', 'core:logs', true),  
        array('worker', 'core:somejunk', false), 
        //array('view', 'admin/settings/general', true), 
    array('view', 'admin/settings/somejunk', false), 
        array('test', 'core:admin_panel', true), 
        array('test', 'core:somejunk', false), 
    array('htmlfunc', 'core:display_table', true), 
    array('htmlfunc', 'core:somejunk', false), 
        array('lib', 'core:admin', true), 
        array('lib', 'core:junk', false), 
        array('controller', 'core:http_requests:http', true), 
        array('controller', 'core:http_requests_somejunk', false)
    );

    // Return
    return $results;

}

/**
 * Test get_class_name() method
 * 
 *     @dataProvider provider_get_class_name
 */
public function test_get_class_name($type, $alias, $package, $parent, $expected)
{

    // Check
    $vars = array(
        'type' => $type, 
        'alias' => $alias, 
        'package' => $package, 
        'parent' => $parent
    );
    $class_name = $this->invoke_method($this->components, 'get_class_name', $vars);

    // Assert
    $this->assertEquals($expected, $class_name, "Invalid response for get_class_name() with, type: $type, alias: $alias, package: $package, parent: $parent");

}

/**
 * provider for test_get_class_name() method.
 */
public function provider_get_class_name()
{

    // Set results
    $results = array(
        array('worker', 'logs', 'core', '', "\\apex\\core\\worker\\logs"), 
        array('lib', 'admin', 'core', '', "\\apex\\core\\admin"), 
        array('htmlfunc', 'display_form', 'core', '', "\\apex\\core\\htmlfunc\\display_form"), 
        array('controller', 'http', 'core', 'http_requests', "\\apex\\core\\controller\\http_requests\\http"), 
        array('tabcontrol', 'debugger', 'core', '', "\\apex\\core\\tabcontrol\\debugger"), 
        array('tabpage', 'line_items', 'core', 'debugger', "\\apex\\core\\tabcontrol\\debugger\\line_items")
    );

    // Return
    return $results;

}

/**
 * Test get_file() method
 * 
 *     @dataProvider provider_get_file
 */
public function test_get_file($type, $alias, $package, $parent, $expected)
{

    // Check file
    $filename = $this->components->get_file($type, $alias, $package, $parent);
    $this->assertEquals($expected, $filename, "Invalid response returned for get_file() with, type: $type, alias: $alias, package: $package: parent: $parent");

}

/**
 * Provider for test_get_file() method
 */
public function provider_get_file()
{

    // Set results
    $results = array(
        array('worker', 'logs', 'core', '', 'src/core/worker/logs.php'), 
        array('htmlfunc', 'display_table', 'core', '', 'src/core/htmlfunc/display_table.php'), 
        array('view', 'admin/settings/general', 'core', '', 'views/php/admin/settings/general.php'), 
        array('test', 'admin_panel', 'core', '', 'tests/core/admin_panel_test.php'), 
        array('controller', 'http', 'core', 'http_requests', 'src/core/controller/http_requests/http.php'), 
        array('lib', 'admin', 'core', '', 'src/core/admin.php'),
        array('tabcontrol', 'debugger', 'core', '', 'src/core/tabcontrol/debugger.php'),  
        array('tabpage', 'line_items', 'core', 'debugger', 'src/core/tabcontrol/debugger/line_items.php')
    );

    // Return
    return $results;

}

/**
 * Test get_tpl_file() method
 * 
 *     @dataProvider provider_get_tpl_file
 */
public function test_get_tpl_file($type, $alias, $package, $parent, $expected)
{

    // Check
    $tpl_file = $this->components->get_tpl_file($type, $alias, $package, $parent);
    $this->assertEquals($expected, $tpl_file, "Invalid response to get_tpl_file() with, type: $type, alias: $alias, package: $package, parent: $parent");

}

/**
 * Provider for get_tpl_file() method
 */
public function provider_get_tpl_file()
{

    // Set results
    $results = array(
        array('view', 'admin/settings/general', 'core', '', 'views/tpl/admin/settings/general.tpl'), 
        array('view', 'public/index', 'core', '', 'views/tpl/public/index.tpl'), 
        array('modal', 'package', 'core', '', 'views/components/modal/core/package.tpl'), 
        array('htmlfunc', 'password_meter', 'core', '', 'views/components/htmlfunc/core/password_meter.tpl'), 
        array('tabpage', 'line_items', 'core', 'debugger', 'views/components/tabpage/core/debugger/line_items.tpl')
    );

    // Return
    return $results;

}

/**
 * Test get_tabcontrol_files() method
 */
public function test_get_tabcontrol_files()
{

    // Get files
    $files = $this->components->get_tabcontrol_files('debugger', 'core');
    $this->assertContains('src/core/tabcontrol/debugger/general.php', $files);
    $this->assertContains('src/core/tabcontrol/debugger/line_items.php', $files);
    $this->assertContains('views/components/tabpage/core/debugger/general.tpl', $files);
    $this->assertContains('views/components/tabpage/core/debugger/line_items.tpl', $files);

}

/**
 * Test get_all_files() method
 * 
 *     @dataProvider provider_get_all_files
 */
public function test_get_all_files($type, $alias, $package, $parent, $expected)
{

    // Get files
    $files = $this->components->get_all_files($type, $alias, $package, $parent);
    $this->assertCount(count($expected), $files);
    foreach ($expected as $chk_file) { 
        $this->assertContains($chk_file, $files);
    }

}

/**
 * Provider for test_get_all_files()
 *
 *     @dataProvider provider_get_all_files
 */
public function provider_get_all_files()
{

    // Set results
    $results = array(
        array('view', 'admin/settings/general', 'core', '', array('views/tpl/admin/settings/general.tpl', 'views/php/admin/settings/general.php')), 
        array('view', 'public/index', 'core', '', array('views/tpl/public/index.tpl', 'views/php/public/index.php')), 
        array('htmlfunc', 'display_form', 'core', '', array('src/core/htmlfunc/display_form.php', 'views/components/htmlfunc/core/display_form.tpl')), 
        array('modal', 'package', 'core', '', array('src/core/modal/package.php', 'views/components/modal/core/package.tpl')), 
        array('tabpage', 'line_items', 'core', 'debugger', array('views/components/tabpage/core/debugger/line_items.tpl', 'src/core/tabcontrol/debugger/line_items.php'))
    );

    // Return
    return $results;

}


}

