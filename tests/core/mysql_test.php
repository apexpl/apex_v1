<?php
declare(strict_types = 1);

namespace tests\core;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\app\db\mysql;
use apex\app\tests\test;


/**
 * Handles unit tests for any code coverage missed during other 
 * unit tests within the mySQL library located 
 * at /src/app/db/mysql.php
 */
class test_mysql extends test
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
 * show_columns
 */
public function test_show_columns()
{

    // Get fresh columns names
    $names = db::show_columns('internal_upgrades');
    $this->assertIsArray($names);
    $this->assertContains('version', $names);

    // Get column names from cache
    $names = db::show_columns('internal_upgrades');
    $this->assertIsArray($names);
    $this->assertContains('version', $names);

}

/**
 * insert - no_table
 */
public function test_insert_no_table()
{

    $this->waitException('table does not exist');
    db::insert('some_junk_invalid_table', array('id' => 543));

}

/**
 * insert -- no_column
 */
public function test_insert_no_column()
{

    $this->waitException('column does not exist');
    db::insert('admin', array('some_junk_colname' => 'test'));

}

/**
 * update -- no_table
 */
public function test_update_no_table()
{

    $this->waitException('table does not exist');
    db::update('some_junk_invalid_table', array('name' => 'test'));

}

/**
 * update - no_column
 */
public function test_update_no_column()
{

    $this->waitException('column does not exist');
    db::update('admin', array('some_junk_invalid_colname' => 'matt'));

}

/**
 * delete -- valid + no_table
 */
public function test_delete()
{

    // Valid
    db::delete('internal_upgrades', 'package = %s', 'some_invalid_junk_package_that_will_never_exist');

    // no_table
    $this->waitException('table does not exist');
    db::delete('from_some_junk_invalid_table', "id = 0");

}

/**
 * get_idrow - no_table
 */
public function test_id_row_no_table()
{

    $this->waitException('table does not exist');
    db::get_idrow('some_unknown_junk_table', 46);

}

/**
 * Query - exception
 */
public function test_query_exception()
{

    $this->waitException('Unable to execute');
    db::query("SELECT * FROM admin WHERE some_junk_column = 'test'");

}

/**
 * num_rows
 */
public function test_num_rows()
{

    $result = db::query("SELECT * FROM admin");
    $num = db::num_rows($result);
    $this->assertTrue(true);

}

/**
 * format_sql -- missing params
 */
public function test_format_sql_params()
{

    $result = db::query("SELECT * FROM admin WHERE date_created = %ds OR date_created = %dt", '2020-02-06', '2020-02-11 15:11:54');
    $this->assertTrue(true);

    // Get exception
    $this->waitException('Invalid variable passed');
    db::query("SELECT * FROM admin WHERE id = %i", 'matt');

}

/**
 * Query - exception within format_sql()
 */
public function test_query_format_exception()
{

    $this->waitException('Unable to execute');
    db::query("SELECT * FROM admin WHERE user = 'test'");

}

/**
 * commit
 */
public function test_commit()
{

    // Begin transaction
    $ok = db::begin_transaction();
    $this->assertTrue(true);

    // Commit
    $ok = db::commit();
    $this->assertTrue($ok);

}

/**
 * rollback
 */
public function test_rollback()
{

    // Begin transaction
    $ok = db::begin_transaction();
    $this->assertTrue(true);

    // Rollback
    $ok = db::rollback();
    $this->assertTrue($ok);

}

}


