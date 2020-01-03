
# Apex - Training Create Unit Test

Last thing before we publish our package to the repository, we need to make sure the code works be developing a 
quick unit test.  Within terminal, type:

`./apex create test training:admin`

This will create a new file at */tests/training/admin_test.php*.  Open the file, and enter the following contents:

~~~php
<?php
declare(strict_types = 1);

namespace tests\training;

use apex\app;
use apex\libc\db;
use apex\libc\debug;
use apex\libc\auth;
use apex\libc\components;
use apex\app\tests\test;


/**
 * Handles the unit tests for the training package.
 */
class test_admin extends test
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
 * Login to admin panel.
 */
public function test_login()
{

    // Login
    app::set_area('admin');
    auth::auto_login(1);

    // Assert true, just so phpUnit doesn't throw a warning
    $this->assertTrue(true);


}

/**
 * Page -- Admin -> Settings -> Lottery
 */
public function test_page_admin_settings_lottery()
{

    // Ensure page loads
    $html = $this->http_request('admin/settings/lottery');
    $this->assertPageTitle('Lottery Settings');
    $this->assertHasFormField('daily_award');
    $this->assertHasSubmit('update', 'Update Lottery Settings');

    // Set request to change settings
    $orig_award = app::_config('training:daily_award');
    $request = array(
        'daily_award' => '75', 
        'submit' => 'update'
    );

    // Send http request
    $html = $this->http_request('admin/settings/lottery', 'POST', $request);
    $this->assertPageTitle('Lottery Settings');
    $this->assertHasCallout('success', 'Successfully updated');
    $this->assertHasSubmit('update', 'Update Lottery Settings');

}

/**
 * Check the crontab job
 */
public function test_crontab_pick_winner()
{

    // Load cron job
    $userid = components::call('process', 'cron', 'pick_winner', 'training');

    // Check database row
    $row = db::get_field("SELECT * FROM lotteries WHERE userid = %i AND DATE(date_added) = DATE(now())", $userid);
    $this->assertNotFalse($row);

}

}

~~~

The above class doesn't cover all functionality, but is just used as a training example.  It contains two methods to test both, 
the Settings->Lottery menu of the administration panel, and the crontab job that we created.  You may view all 
details on unit tests within Apex at the [Unit Tests](../tests.md) page of the documentation.



### Execute Unit Tests

To execute the unit tests, the first thing you should probably do is quickly modify the /phpunit.xml file, and 
change line # 12 to:

~~~
<directory suffix="_test.php">./tests/training</directory>
~~~

This will ensure only unit tests within our "training" package will be executed.  To execute the unit tests, within terminal type:

`./vendor/phpunit/phpunit/phpunit`

This will automatically execute all unit tests, and should come back as successful.


### Next

Now that both, development and unit tests are complete, and let's go ahead 
and [Publish the Package](publish_package.md) to our repository.





