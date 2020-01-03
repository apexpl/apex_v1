<?php
declare(strict_types = 1);

namespace apex\core;

use apex\app;
use apex\libc\db;
use apex\libc\debug;
use apex\libc\components;

/**
 * Handles the dashboard widegts for the core package, plus other 
 * dashboard functionality such as creating the profile options, etc.
 */
class dashboard
{

/**
 * Get number of admins online
    *
 * @return string The number of admins currencly online.
 */
public function admin_top_admins_online()
{
    return (string) 1;
}

/**
 * Get blank right sidebar item.
 *
 * @return string Contents of the item.
 */
public function admin_right_blank()
{

    // Return
    return "Blank / default dashboard item, which will be replaced once additional packages are installed.";

}



/**
 * Create profile options.
 *
 * @param string $profile_id The id# of the selected dashboard profile.
 *
 * @return string The resulting HTML options.
 */
public static function create_profile_options(string $profile_id)
{

    // Go through profiles
    $options = '';
    $rows = db::query("SELECT * FROM dashboard_profiles ORDER BY id");
    foreach ($rows as $row) { 

        // Get name
        $name = $row['area'] == 'admin' ? 'Administration Panel -- ' : 'Member Area -- ';
        if ($row['is_default'] == 1) { 
            $name .= 'Default';
        } else { 
            $user_class = $row['area'] == 'admin' ? admin::class : user::class;
            $user = app::make($user_class, ['id' => (int) $row['userid']]);
            $profile = $user->load();
            $name .= $profile['username'];
        }

        // Add to options
        $chk = $profile_id == $row['id'] ? 'selected="selected"' : '';
        $options .= "<option value=\"$row[id]\" $chk>$name</option>";

    }

    // Return
    return $options;

}

/**
 * Create item options
 *
 * @param string $selected The ID# of the selected dashboard item.
 *
 * @param string The resulting HTML options.
 */
public static function create_item_options(string $selected)
{

    // Get area
    if (app::get_action() == 'change') { 
        $profile_id = app::_post('dashboard');

    } elseif (app::has_get('profile_id')) { 
        $profile_id = app::_get('profile_id');

    } else { 
        $profile_id = db::get_field("SELECT id FROM dashboard_profiles WHERE area = 'admin' AND is_default = 1");
    }
    $area = db::get_field("SELECT area FROM dashboard_profiles WHERE id = %i", $profile_id);

    // Go through items
    $options = '';
    $rows = db::query("SELECT * FROM dashboard_items WHERE area = %s ORDER BY type,title", $area);
    foreach ($rows as $row) { 

        // Get name
        $package_name = db::get_field("SELECT name FROM internal_packages WHERE alias = %s", $row['package']);
        $name = ucwords($row['type']) . ' -- ' . $row['title'] . ' (' . $package_name . ')';

        // Add to options
    $chk = $selected == $row['id'] ? 'selected="selected"' : '';
        $options .= "<option value=\"$row[id]\" $chk>$name</option>";
    }

    // Return
    return $options;

}

/**
 * Get a dashboard profile.
 *
 * Determines the correct dashboard profile to display, then obtains the contents 
 * of all top and right items.
 *
 * @return array The dashboard profile.
 */
public function get_profile():array
{

    // Determine the profile ID
    if (!$profile = db::get_row("SELECT * FROM dashboard_profiles WHERE area = %s AND userid = %i", app::get_area(), app::get_userid())) { 
        $profile = db::get_row("SELECT * FROM dashboard_profiles WHERE area = %s AND is_default = 1", app::get_area());
    }

    // Start results
    $results = array(
        'id' => $profile['id'], 
        'top_items' => array(), 
        'right_item' => array()
    );

    // Get top and right items
        foreach (array('top','right') as $type) { 

        // Go through items
        $rows = db::query("SELECT * FROM dashboard_profiles_items WHERE profile_id = %i AND type = %s ORDER BY id", $profile['id'], $type);
        foreach ($rows as $row) { 

            // Get the item row
            $item = db::get_row("SELECT * FROM dashboard_items WHERE type = %s AND package = %s AND area = %s AND alias = %s", $row['type'], $row['package'], $profile['area'], $row['alias']);


            // Load the needed class
            $client = app::makeset("\\apex\\" . $row['package'] . "\\dashboard");

            // Get contents
            $func_name = $profile['area'] . '_' . $row['type'] . '_' . $row['alias'];
            $contents = $client->$func_name();

            // Set vars
            $results[$type . '_items'][] = array(
                'title' => $item['title'], 
                'contents' => $contents, 
                'divid' => $item['divid'], 
                'panel_class' => $item['panel_class']
            );
        }
    }

    // Get tab control
    $client = components::load('htmlfunc', 'display_tabcontrol', 'core');
    $results['tabcontrol'] = $client->process('', array('tabcontrol' => 'core:dashboard', 'profile_id' => $results['id']));

    // Return
    return $results;

}


}




