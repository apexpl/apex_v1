<?php
declare(strict_types = 1);

namespace apex\app\pkg;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\svc\redis;
use apex\app\exceptions\ComponentException;
use apex\app\exceptions\PackageException;
use apex\app\pkg\pkg_component;
use apex\core\notification;


/**
 * Handles package configuration files (/etc/ALIAS/package.php), installation 
 * loading and installation of all components within 
 */
class package_config
{



    // Properties
    public $pkg_alias;
    public $pkg_dir;

/**
 * Construct 
 *
 * @param string $pkg_alias The alias of the package to load / manage
 */
public function __construct(string $pkg_alias = '')
{ 

    // Set variables
    $this->pkg_alias = $pkg_alias;
    $this->pkg_dir = SITE_PATH . '/etc/' . $pkg_alias;

    // Debug
    debug::add(3, tr("Initialized package, {1}", $pkg_alias));

}

/**
 * Load package configuration 
 *
 * Loads a package configuration file, and ensures any omitted arrays within 
 * package configuration are set as blank arrays to avoid errors in the rest 
 * of the class. 
 */
public function load()
{ 

    // Ensure package.php file exists
    if (!file_exists($this->pkg_dir . '/package.php')) { 
        throw new PackageException('config_not_exists', $this->pkg_alias);
    }

    // Load package file
    require_once($this->pkg_dir . '/package.php');
    $class_name = "\\apex\\pkg_" . $this->pkg_alias;

    // Initiate package class
    try { 
        $pkg = new $class_name();
    } catch (Exception $e) { 
        throw new PackageException('config_no_load', $this->pkg_alias);
    }

    // Blank out needed arrays
    $vars = array(
        'config',
        'hash',
        'menus',
        'ext_files',
        'boxlists',
        'placeholders',
        'notifications', 
        'dashboard_items', 
        'dependencies', 
        'composer_dependencies'
    );

    foreach ($vars as $var) { 
        if (!isset($pkg->$var)) { $pkg->$var = array(); }
    }

    // Debug
    debug::add(2, tr("loaded package configuration, {1}", $this->pkg_alias));

    // Return
    return $pkg;

}

/**
 * Install / update package configuration 
 *
 * Goes through the package.php configuration file, and updates the database 
 * as necessary.  Ensures to update existing records as necessary, and delete 
 * records that have been removed from the package.php file. 
 *
 * @param mixed $pkg The loaded package configuration.
 */
public function install_configuration($pkg = '')
{ 

    // Debug
    debug::add(2, tr("Starting configuration install / scan of package, {1}", $this->pkg_alias));

    // Load package, if needed
    if (!is_object($pkg)) { 
        $pkg = $this->load();
    }

    // Config vars
    $this->install_config_vars($pkg);

    // Hashes
    $this->install_hashes($pkg);

    // Install menus
    $this->install_menus($pkg);

    // Install boxlists
    $this->install_boxlists($pkg);

    // Install placeholders
    $this->install_placeholders($pkg);

    // Install dashboard items
    $this->install_dashboard_items($pkg);

    // Install composer dependencies
    $this->install_composer_dependencies($pkg);

    // Debug
    debug::add(2, tr("Completed configuration install / scan of package, {1}", $this->pkg_alias));

}

/**
 * Install configuration variables 
 *
 * Adds / updates the configuration variables as necessary from the 
 * package.php $this->config() array. 
 *
 * @param mixed $pkg The loaded package configuration.
 */
protected function install_config_vars($pkg)
{ 

    // Debug
    debug::add(3, tr("Starting install of config vars for package, {1}", $this->pkg_alias));

    // Add config vars
    foreach ($pkg->config as $alias => $value) { 
        $comp_alias = $this->pkg_alias . ':' . $alias;
        pkg_component::add('config', $comp_alias, (string) $value);

        // Add to redis
        if (!redis::hexists('config', $comp_alias)) { 
            redis::hset('config', $comp_alias, $value);
        }
    }

    // Check for deletions
    $chk_config = db::get_column("SELECT alias FROM internal_components WHERE package = %s AND type = 'config'", $this->pkg_alias);
    foreach ($chk_config as $chk) { 
        if (in_array($chk, array_keys($pkg->config))) { continue; }
        $comp_alias = $this->pkg_alias . ':' . $chk;
        pkg_component::remove('config', $comp_alias);
        redis::hdel('config', $comp_alias);
    }

    // Debug
    debug::add(3, tr("Completed install of configuration variables for package, {1}", $this->pkg_alias));

}

/**
 * Install hashes 
 *
 * Adds / updates / deletes the hashes within the package.php file's 
 * $this->hash array.  Hashes are used as key-value paris to easily populate 
 * select / radio / checkbox lists. 
 * 
 * @param mixed $pkg The loaded package configuration.
 */
protected function install_hashes($pkg)
{ 

    // Debug
    debug::add(3, tr("Starting hashes install of package, {1}", $this->pkg_alias));

    // Add needed hashes
    foreach ($pkg->hash as $hash_alias => $vars) { 
        if (!is_array($vars)) { continue; }

        // Add / update hash
        $comp_alias = $this->pkg_alias . ':' . $hash_alias;
        redis::hset('hash', $comp_alias, json_encode($vars));
        pkg_component::add('hash', $comp_alias);

        // Check for var deletion
        $chk_vars = db::get_column("SELECT alias FROM internal_components WHERE type = 'hash_var' AND package = %s AND parent = %s", $this->pkg_alias, $hash_alias);
        foreach ($chk_vars as $chk) { 
            if (in_array($chk, array_keys($vars))) { continue; }
            pkg_component::remove('hash_var', $comp_alias . ':' . $chk);
        }

        // Go through variables
        $order_num = 1;
        foreach ($vars as $key => $value) { 
            pkg_component::add('hash_var', $comp_alias . ':' . $key, $value, $order_num);
        $order_num++; }
    }

    // Check for deletions
    $chk_hash = db::get_column("SELECT alias FROM internal_components WHERE type = 'hash' AND package = %s", $this->pkg_alias);
    foreach ($chk_hash as $chk) { 
        if (in_array($chk, array_keys($pkg->hash))) { continue; }
        pkc_component::remove('hash', $this->pkg_alias . ':' . $chk);
        redis::hdel('hash', $this->pkg_alias . ':' . $chk);
    }

    // Debug
    debug::add(3, tr("Completed hashes install of package, {1}", $this->pkg_alias));

}

/**
 * Install menus 
 *
 * Adds / updates all menus within the package.php configuration file. 
 *
 * @param mixed $pkg The loaded package configuration.
 */
protected  function install_menus($pkg)
{ 

    // Debug
    debug::add(3, tr("Start menus install of package, {1}", $this->pkg_alias));

    // Go through menus
    $done = array();
    foreach ($pkg->menus as $vars) { 
        if (!isset($vars['area'])) { continue; }

        // Set variables
        $area = $vars['area'];
        $position = $vars['position'] ?? 'bottom';
        $type = $vars['type'] ?? 'internal';
        $parent = $vars['parent'] ?? '';
        $alias = $vars['alias'] ?? '';
        $name = $vars['name'] ?? ucwords(str_replace("_", " ", $alias));
        $icon = $vars['icon'] ?? '';
        $url = $vars['url'] ?? '';
        $require_login = $vars['require_login'] ?? 0;
        $require_nologin = $vars['require_nologin'] ?? 0;
        $submenus = isset($vars['menus']) && is_array($vars['menus']) ? $vars['menus'] : array();

        // Add to $done array
    $done[] = implode(":", array($type, $area, $parent, $alias));

        // Add menu, if needed
        if ($alias != '') { 
            $this->add_single_menu($area, $parent, $alias, $name, $position, $type, $icon, $url, $require_login, $require_nologin);
            $parent = $alias;
        }

        // Go through submenus, if needed
        $last_sub_alias = '';
        foreach ($submenus as $sub_alias => $sub_name) { 
            $position = $last_sub_alias == '' ? 'top' : 'after ' . $last_sub_alias;
            $this->add_single_menu($area, $parent, $sub_alias, $sub_name, $position, 'internal', '', '', $require_login, $require_nologin);
            $last_sub_alias = $sub_alias;
            $done[] = implode(":", array('internal', $area, $parent, $sub_alias));
        }
    }

    // Delete needed menus
    $rows = db::query("SELECT * FROM cms_menus WHERE package = %s ORDER BY id", $this->pkg_alias);
    foreach ($rows as $row) { 
        $chk = implode(":", array($row['link_type'], $row['area'], $row['parent'], $row['alias']));
        if (in_array($chk, $done)) { continue; }
        db::query("DELETE FROM cms_menus WHERE id = %i", $row['id']);
    }

    // Update redis database
    $this->update_redis_menus();

    // Debug
    debug::add(3, tr("Completed menus install of package, {1}", $this->pkg_alias));

}

/**
 * Add / update single menu 
 *
 * Adds a single menu to the 'cms_menus' table, and is used by the above 
 * install_menu() function. 
 *
 * @param string $area The area the menu is for (usually 'admin', 'public', or 'members')
 * @param string $parent The parent alias of the menu, used for sub-menus.
 * @param string $alias The alias for the menu, used within the URI.
 * @param string $name Optional display name.  If omitted, ucwords($alias) is used.
 * @param string $position The position of the menu.  See documentation for details.
 * @param string $type The type of menu.  Can be: 'internal', 'parent', 'header', 'external''.  Defaults to 'internal'.
 * @param string $icon The icon of the menu, if applicab le (eg. fa fa-fw fa-users).
 * @param string $url Only applicable for menus with type 'external', and is the URL destination of the menu.
 * @param int $require_login Whether or not login is required to display this menu.  Used for public area to display different menus on public site depending whether user is logged in or not.
 * @param int $require_nologin Whether or not to only display this menu when not logged in.
 */
protected function add_single_menu(string$area, string $parent, string $alias, string $name, string $position = 'bottom', string $type = 'internal', string $icon = '', string $url = '', int $require_login = 0, int $require_nologin = 0)
{ 

    // Debug
    debug::add(3, tr("Adding single menu, package: {1}, area: {2}, parent: {3}, alias: {4}", $this->pkg_alias, $area, $parent, $alias));

    // Check if menu exists
    if ($menu_id = db::get_field("SELECT id FROM cms_menus WHERE area = %s AND parent = %s AND alias = %s", $area, $parent, $alias)) { 

        // Update db
        db::update('cms_menus', array(
            'require_login' => $require_login,
            'require_nologin' => $require_nologin,
            'icon' => $icon,
            'url' => $url,
            'name' => $name),
        "id = %i", $menu_id);

    // Add new menu
    } else { 

        // Get position
        $order_num = $this->get_menu_position($area, $parent, $position);

        // Add to db
        db::insert('cms_menus', array(
            'package' => $this->pkg_alias,
            'area' => $area,
            'require_login' => $require_login,
            'require_nologin' => $require_nologin,
            'order_num' => $order_num,
            'link_type' => $type,
            'icon' => $icon,
            'parent' => $parent,
            'alias' => $alias,
            'name' => $name,
            'url' => $url)
        );
        $menu_id = db::insert_id();

    }

}

/**
 * Get menu position. 
 *
 * Gets the 'order_num' / position of a menu.  Modifies the 'order_num' column 
 * as necessary to make room for the menu as well. 
 *
 * @param string $area The area the menu is in (eg. 'admin', 'public', or 'members')
 * @param string $parent The parent alias of the menu, if it's a sub-menu.
 * @param string $position The position of the menu.  See documentation for details.
 *
 * @return int The 'order_num' of the menu.
 */
protected function get_menu_position(string $area, string $parent, string $position):int
{ 

    // Check for before / after
    if (preg_match("/^(before|after) (.+)$/i", $position, $match)) { 

        // Get current row
        if ($row = $this->menu_get_current_position_row($area, $match[2], $match[1], $parent)) { 
            $opr = $match[1] == 'before' ? '>=' : '>';
            db::query("UPDATE cms_menus SET order_num = order_num + 1 WHERE area = %s AND parent = %s AND order_num $opr %i", $area, $parent, $row['order_num']);
            if ($match[1] == 'after') { $row['order_num']++; }
            return (int) $row['order_num'];
        } else { 
            $position = $match[1] == 'before' ? 'top' : 'bottom';
        }
    }

    // Get order num
    if ($position == 'top') { 
        $order_num = 1;
        db::query("UPDATE cms_menus SET order_num = order_num + 1 WHERE area = %s AND parent = %s", $area, $parent);
    } else { 
        $order_num = db::get_field("SELECT max(order_num) FROM cms_menus WHERE area = %s AND parent = %s", $area, $parent);
        $order_num++;
    }

    // Return
    return (int) $order_num;

}

/**
 * Get current menu row / position 
 *
 * Get the current menu row, where our new menu is going to be placed.  Used 
 * in case if new menu is before / after a header row, to get the next / last 
 * header row. 
 *
 * @param string $area The area of the menu (admin, members, etc.)
 * @param string $alias The alias of the menu we're looking for
 * @param string $position Must be either 'before' or 'after'
 * @param string $parent The alias of the parent menu, if applicable
 *
 * @return array The current menu row, or false if one is not found
 */
protected function menu_get_current_position_row(string $area, string $alias, string $position, string $parent = '')
{ 

    // Check for row
    if (!$row = db::get_row("SELECT * FROM cms_menus WHERE area = %s AND parent = %s AND alias = %s", $area, $parent, $alias)) { 
        return false;
    }

    // Check for header row
    if ($row['link_type'] == 'header') { 

        // Get next header row
        $order_num = $row['order_num'];
        $opr = $position == 'before' ? '<' : '>';
        if (!$row = db::get_row("SELECT * FROM cms_menus WHERE area = %s AND parent = %s AND order_num $opr $order_num AND link_type = 'header'", $area, $parent)) { 
            return false;
        }
    }

    // Return
return $row;

}

/**
 * Update menus in redis 
 *
 * Update menus in the redis database 
 */
public function update_redis_menus()
{ 

    // Get areas
    $areas = db::get_column("SELECT DISTINCT area FROM cms_menus");

    // Go through areas
    foreach ($areas as $area) { 

        $menus = array('__main' => array());
        $order_num = 1;
        $rows = db::query("SELECT * FROM cms_menus WHERE is_active = 1 AND area = '$area' AND parent = '' ORDER BY order_num");
        foreach ($rows as $row) { 
            //$row['order_num'] = $order_num;
            //db::query("UPDATE cms_menus SET order_num = $order_num WHERE id = %i", $row['id']);
            array_push($menus['__main'], $row);
            $order_num++;

            // Go through sub-menus
            $corder_num = 1;
            $crows = db::query("SELECT * FROM cms_menus WHERE is_active = 1 AND area = %s AND parent = %s ORDER BY order_num", $row['area'], $row['alias']);
            foreach ($crows as $crow) { 
                if (!isset($menus[$row['alias']])) { $menus[$row['alias']] = array(); }
                //$crow['order_num'] = $corder_num;
                //db::query("UPDATE cms_menus SET order_num = $corder_num WHERE id = %i", $crow['id']);
                array_push($menus[$row['alias']], $crow);
                $corder_num++;
            }
        }

        // Update redis database
        redis::hset('cms:menus', $area, json_encode($menus));
    }

}

/**
 * Install boxlists 
 *
 * Installs / updates the boxlists from the package.php configuration file. 
 * These are generally used for settings pages, such as user / financial 
 * settings in the admin panel. 
 *
 * @param mixed $pkg The loaded package configuration.
 */
protected function install_boxlists($pkg)
{ 

    // Debug
    debug::add(3, tr("Starting boxlists install of package, {1}", $this->pkg_alias));

    // Go through boxlists
    $done = array();
    foreach ($pkg->boxlists as $vars) { 
        list($package, $alias) = explode(":", $vars['alias'], 2);
        $done[] = implode(":", array($package, $alias, $vars['href']));

        if ($row = db::get_row("SELECT * FROM internal_boxlists WHERE package = %s AND alias = %s AND href = %s", $package, $alias, $vars['href'])) { 
            db::update('internal_boxlists', array(
                'title' => $vars['title'],
                'description' => $vars['description']),
            "id = %i", $row['id']);

        } else { 

            // Get order num
            $order_num = db::get_field("SELECT max(order_num) FROM internal_boxlists WHERE package = %s AND alias = %s", $package, $alias);
            if ($order_num == '') { $order_num = 0; }
            $order_num++;

            // Add to db
            db::insert('internal_boxlists', array(
                'owner' => $this->pkg_alias,
                'package' => $package,
                'alias' => $alias,
                'order_num' => $order_num,
                'href' => $vars['href'],
                'title' => $vars['title'],
                'description' => $vars['description'])
            );

        }
    }

    // Delete needed boxlists
    $rows = db::query("SELECT * FROM internal_boxlists WHERE owner = %s", $this->pkg_alias);
    foreach ($rows as $row) { 
        $chk = implode(':', array($row['package'], $row['alias'], $row['href']));
        if (in_array($chk, $done)) { continue; }
        db::query("DELETE FROM internal_boxlists WHERE id = %i", $row['id']);
    }

    // Debug
    debug::add(3, tr("Completed boxlists install of package, {1}", $this->pkg_alias));

}

/**
 * Install placeholders 
 *
 * @param mixed $pkg The loaded package configuration.
 */
protected function install_placeholders($pkg)
{ 

    // Debug
    debug::add(3, tr("Starting placeholders install of package {1}", $this->pkg_alias), 'info');

    // Go through placeholders
    $done = array();
    foreach ($pkg->placeholders as $uri => $value) { 
        $aliases = is_array($value) ? $value : array($value);

        // Go through aliases
        foreach ($aliases as $alias) { 
            $done[] = $uri . ':' . $alias;

            // Check if exists
            $exists = db::get_field("SELECT count(*) FROM cms_placeholders WHERE package = %s AND uri = %s AND alias = %s", $this->pkg_alias, $uri, $alias);
            if ($exists > 0) { continue; }

            // Add to database
            db::insert('cms_placeholders', array(
                'package' => $this->pkg_alias,
                'uri' => $uri,
                'alias' => $alias,
                'contents' => '')
            );
            
        }
    }

    // Delete needed placeholders
    $rows = db::query("SELECT * FROM cms_placeholders WHERE package = %s", $this->pkg_alias);
    foreach ($rows as $row) { 
        $alias = $row['uri'] . ':' . $row['alias'];
        if (in_array($alias, $done)) { continue; }
        db::query("DELETE FROM cms_placeholders WHERE id = %i", $row['id']);
    }

}

/**
 * Install dashboard items
 */
protected function install_dashboard_items($pkg)
{

    // Return, if no dashboard items
    if (!isset($pkg->dashboard_items)) { return; }

    // Add dashboard items
    $done = array();
    foreach ($pkg->dashboard_items as $vars) { 

        // Set variables
        $done[] = $vars['type'] . '_' . $vars['alias'];
        $divid = $vars['divid'] ?? '';
        $panel_class = $vars['panel_class'] ?? '';

        // Update, if already exists
        if ($row = db::get_row("SELECT * FROM dashboard_items WHERE package = %s AND alias = %s AND type = %s AND area = %s", $this->pkg_alias, $vars['alias'], $vars['type'], $vars['area'])) { 

            // Update database
            db::update('dashboard_items', array(
                'area' => $vars['area'], 
                'divid' => $divid,
                'panel_class' => $panel_class,  
                'title' => $vars['title'], 
                'description' => $vars['description']), 
            "id = %i", $row['id']);
            continue;

        }

        // Add new item
        db::insert('dashboard_items', array(
            'package' => $this->pkg_alias, 
            'area' => $vars['area'], 
            'type' => $vars['type'], 
            'divid' => $divid, 
            'panel_class' => $panel_class, 
            'alias' => $vars['alias'], 
            'title' => $vars['title'], 
            'description' => $vars['description'])
        );

    }

    // Delete needed dashboard items
    $rows = db::query("SELECT * FROM dashboard_items WHERE package = %s", $this->pkg_alias);
    foreach ($rows as $row) { 
        $alias = $row['type'] . '_' . $row['alias'];
        if (in_array($alias, $done)) { continue; }
        db::query("DELETE FROM dashboard_items WHERE id = %i", $row['id']);
    }

}

/**
 * Install default dashboard items
 */
public function install_default_dashboard_items($pkg)
{

    // Return, if no items
    if (!isset($pkg->dashboard_items)) { return; }

    // Go through items
    foreach ($pkg->dashboard_items as $vars) { 
        if (!isset($vars['is_default'])) { continue; }
        if ($vars['is_default'] != 1) { continue; }

        // Get profile ID
        if (!$profile_id = db::get_field("SELECT id FROM dashboard_profiles WHERE area = %s AND is_default = 1", $vars['area'])) { 
            continue;
        }

        // Delete core items, if they exist
        db::query("DELETE FROM dashboard_profiles_items WHERE profile_id = %i AND type = %s AND package = %s", $profile_id, $vars['type'], $this->pkg_alias);

        // Add to database
        db::insert('dashboard_profiles_items', array(
            'profile_id' => $profile_id, 
            'type' => $vars['type'], 
            'package' => $this->pkg_alias, 
            'alias' => $vars['alias'])
        );
    }

}

/**
 * Reorder tab control 
 *
 * Re-orders the tab pages in a tab control  Used when a tab page is added / 
 * removed from a tab control. 
 *
 * @param string $package The package alias the tab control belongs to.
 * @param string $alias The alias of the tab control
 */
protected function reorder_tabcontrol(string $package, string $alias)
{ 

    // Debug
    debug::add(3, tr("Starting re-order of tab control, package: {1}, alias: {2}", $package, $alias));

    // Load tab control
    if (!$tab = components::load('tabcontrol', $alias, $package)) { 
        throw new ComponentException('not_exists', 'tabcontrol', '', $alias, $package);
    }

    // Go through initial pages
    $order_num = 1;
    foreach ($tab::$tabpages as $tab_alias => $tab_name) { 
        db::query("UPDATE internal_components SET order_num = $order_num WHERE type = 'tabpage' AND package = %s AND parent = %s AND alias = %s", $package, $alias, $tab_alias);
        $order_num++;
    }

    // Go through all extra pages
    $pages = db::get_column("SELECT alias FROM internal_components WHERE type = 'tabpage' AND package = %s AND parent = %s", $package, $alias);
    foreach ($pages as $page) { 
        if (in_array($page, array_keys($tab::$tabpages))) { continue; }

        // Get position
        $position = 'bottom';
        $php_file = SITE_PATH . '/data/tabcontrol/' . $package . '/' . $alias . '/' . $page . '.php';
        if (file_exists($php_file)) { 
            require_once($php_file);

            $class_name = 'tabpage_' . $package . '_' . $alias . '_' . $page;
            $client = new $class_name();
            if (isset($client::$position)) { $position = $client::$position; }
        }

        // Get new order num
        if (preg_match("/^(before|after) (.+)/i", $position, $match)) { 
            if ($tmp_order_num = db::get_field("SELECT order_num FROM internal_components WHERE type = 'tabpage' AND package = %s AND parent = %s AND alias = %s", $package, $alias, $match[2])) { 
                $opr = $match[1] == 'before' ? '>=' : '>';
                db::query("UPDATE internal_components SET order_num = order_num + 1 WHERE type = 'tabpage' AND package = %s AND parent = %s AND order_num $opr %i", $package, $alias, $tmp_order_num);
                if ($match[1] == 'after') { $tmp_order_num++; }
            } else { $position = 'bottom'; }

        } elseif ($position == 'top') { 
            db::query("UPDATE internal_components SET order_num = order_num + 1 WHERE type = 'tabpage' AND package = %s AND parent = %s", $package, $alias);
            $tmp_order_num = 1;
        } else { $position = 'bottom'; }

        // Bottom
        if ($position == 'bottom') { $tmp_order_num = ($order_num + 1); }
        $order_num++;

        // Update db
        db::query("UPDATE internal_components SET order_num = $tmp_order_num WHERE type = 'tabpage' AND package = %s AND parent = %s AND alias = %s", $package, $alias, $page);
    }

    // Debug
    debug::add(3, tr("Completed re-order of tab control, package: {1}, alias: {2}", $package, $alias));

}

/**
 * Install notificationsl.  Only executed during initial package install, and 
 * never again. 
 * 
 * @param mixed $pkg The loaded package configuration.
 */
public function install_notifications($pkg)
{ 

    // Go through notifications
    foreach ($pkg->notifications as $data) { 
        $data['contents'] = base64_decode($data['contents']);

        $client = new notification();
        $client->create($data);
    }

}

/**
 * Install composer dependencies
 *
 * @param object $pkg The loaded package objct.
 */
public function install_composer_dependencies($pkg)
{

    // Initial check
    if (!isset($pkg->composer_dependencies)) { return; }
    if (!is_array($pkg->composer_dependencies)) { return; }

    // Get composer.json file
    $vars = json_decode(file_get_contents(SITE_PATH . '/composer.json'), true);

    // Go through dependencies
    foreach ($pkg->composer_dependencies as $key => $value) { 
        $vars['require'][$key] = $value;
    }

    // Save composer.json file
    file_put_contents(SITE_PATH . '/composer.json', json_encode($vars, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

}


}

