<?php
declare(strict_types = 1);

namespace tests\core;

use apex\app;
use apex\libc\db;
use apex\libc\debug;
use apex\app\web\view;
use apex\app\tests\test;


/**
 * Handles all tests for the view / template engine located 
 * at /src/app/web/view.php
 */
class test_view extends test
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
 * 404 - Page Not Found
 */
public function test_404()
{

    $html = $this->http_request('some_junk_and_invalid_page');
    $this->assertPageTitle('Page Not Found');

}

/**
 * Invalid <a:function> tags
 */
public function test_invalid_function_tags()
{

    // No alias
    file_put_contents(SITE_PATH . '/views/tpl/public/utest.tpl', "<h1>Unit Test</h1>\n\n<a:function als=\"core:display_table\">\n\n");
    $html = $this->http_request('utest');
    $this->assertPageTitle('Unit Test');
    $this->assertPageContains("No 'alias' attribute exists");

    // Invalid alias
    file_put_contents(SITE_PATH . '/views/tpl/public/utest.tpl', "<h1>Unit Test</h1>\n\n<a:function alias=\"core:some_invalid_junk_function\">\n\n");
    $html = $this->http_request('utest');
    $this->assertPageContains('does not exist');

    // Clean up
    @unlink(SITE_PATH . '/views/tpl/public/utest.tpl');

}

/**
 * Test if / else tags
 */
public function test_if_else_tags()
{

    // Test if
    app::set_userid(0);
    file_put_contents(SITE_PATH . '/views/tpl/public/utest.tpl', "<h1>Unit Test</h1>\n\n<a:if ~userid~ == 0>\n    No user\n<a:else>\n     Yes logged in\n</a:if>\n\n");
    $html = $this->http_request('utest');
    $this->assertPageTitle('Unit Test');
    $this->assertPageContains('No user');

    // Test <a:else>
    app::set_userid(1);
    $html = $this->http_request('utest');
    $this->assertPageTitle('Unit Test');
    $this->assertPageContains('Yes logged in');

    // Clean up
    @unlink(SITE_PATH . '/views/tpl/public/utest.tpl');

}


}


