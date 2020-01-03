<?php
declare(strict_types = 1);

namespace apex\core\tabcontrol\dashboard;

use apex\app;
use apex\libc\db;
use apex\libc\debug;

/**
 * Handles the specifics of the one tab page, and is 
 * executed every time the tab page is displayed.
 */
class trans_pending 
{

    // Page variables
    public $position = 'bottom';
    public $name = 'Trans_pending';

/**
 * Process the tab page.
 *
 * Executes every time the tab control is displayed, and used 
 * to execute any necessary actions from forms filled out 
 * on the tab page, and mianly to treieve variables and assign 
 * them to the template.
 *
 *     @param array $data The attributes containd within the <e:function> tag that called the tab control
 */
public function process(array $data = array()) 
{


}

}

