<?php
declare(strict_types = 1);

namespace apex\app\web;

use apex\app;
use apex\libc\{db, redis, debug, io};


/**
 * Class that handles all base CMS functionaliaty that 
 * comes with the core package, such as layouts / titles of 
 * pages, placeholders, menus, etc.
 */
class cms
{


/**
 * Clear all page titles / layouts
 *
 * @param string $area The area to clean (ie. public / members).
 */
public function clear_titles_layouts(string $area)
{

    // Delete existing
    db::query("DELETE FROM cms_layouts WHERE area = %s", $area);

    // Delete from redis cms:titles
$keys = redis::hkeys('cms:titles');
    foreach ($keys as $key) { 
        if (!preg_match("/^$area\//", $key)) { continue; }
        redis::hdel('cms:titles', $key);
    }

    // Delete from redis cms:layouts
    $keys = redis::hkeys('cms:layouts');
    foreach ($keys as $key) { 
        if (!preg_match("/^$area\//", $key)) { continue; }
        redis::hdel('cms:layouts', $key);
    }

    // Debug
    debug::add(2, tr("Cleared all page titles / layouts from area {1}", $area));

}

/**
 * Add page title / layout
 *
 * @param string $area The area of the page (ie. public / members).
 * @param string $file The filename of the page
 * @param string $title The title of the page
 * @param string $layout The layout of the page, defaults to 'default'.
 */
public function add_page_title_layout(string $area, string $file, string $title = '', string $layout = 'default')
{

    // Delete, if exists
    db::query("DELETE FROM cms_layouts WHERE area = %s AND filename = %s", $area, $file);

        // Add to database
        db::insert('cms_layouts', array(
            'area' => $area, 
            'layout' => $layout, 
            'title' => $title, 
            'filename' => $file)
        );

        // Update redis
        $key = $area . '/' . $file;
        redis::hset('cms:titles', $key, $title);
        redis::hset('cms:layouts', $key, $layout);

}

/**
 * Delete page title / layout from database
 *
 * @param string $area The area of the page (ie. public / members)
 * @param string $uri The URI of the page.
 */
public function delete_page(string $area, string $uri)
{

    // Delete from database
    db::query("DELETE FROM cms_layouts WHERE area = %s AND filename = %s", $area, $uri);

    // Delete from redis
    $key = $area . '/' . $uri;
    redis::hdel('cms:titles', $key);
    redis::hdel('cms:layouts', $key);

}

/**
 * Get layout options
 *
 * @param string $theme_alias The alias of the theme to create layout options for.
 * @param string $selected Optional selected layout.
 *
 * @return string The resulting HTML options.
 */
public function get_layout_options(string $theme_alias, string $selected = ''):string
{

    // Initialize
    $options = '';
    $layout_files = io::parse_dir(SITE_PATH . '/views/themes/' . $theme_alias . '/layouts');

    // Go through layouts
    foreach ($layout_files as $file) { 
        $file = preg_replace("/\.tpl$/", "", $file);
        $chk = $selected == $file ? 'selected="selected"' : '';
        $options .= "<option value=\"$file\" $chk>" . ucwords(str_replace('_', ' ', $file)) . "</option>";
    }

    // Return
    return $options;

}
}

