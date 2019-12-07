<?php
declare(strict_types = 1);

namespace apex\core\tabcontrol;

use apex\app;
use apex\svc\db;
use apex\svc\debug;


/**
 * Class that handles the tab control, and is executed 
 * every time the tab control is displayed.
 */
class dashboard
{

    // Define tab pages
    public $tabpages = array();

/**
 * Process the tab control.
 *
 * Is executed every time the tab control is displayed, 
 * is used to perform any actions submitted within forms 
 * of the tab control, and mainly to retrieve and assign variables 
 * to the template engine.
 *
 *     @param array $data The attributes contained within the <e:function> tag that called the tab control.
 */
public function __construct(array $data)
{

    // Get area
    $profile_id = $data['profile_id'] ?? 0;
    $area = db::get_field("SELECT area FROM dashboard_profiles WHERE id = %i", $profile_id);

    // Get tab pages
    $rows = db::query("SELECT * FROM dashboard_profiles_items WHERE profile_id = %i AND type = 'tab' ORDER BY id", $profile_id);
    foreach ($rows as $row) { 
        $alias = $row['package'] . '_' . $row['alias'];
        $this->tabpages[$alias] = db::get_field("SELECT title FROM dashboard_items WHERE area = %s AND type = %s AND package = %s AND alias = %s", $area, $row['type'], $row['package'], $row['alias']);
    }



}

}

