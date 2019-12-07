<?php
declare(strict_types = 1);

namespace apex\app\web;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\svc\msg;
use apex\svc\redis;
use apex\svc\cache;
use apex\svc\auth;
use apex\svc\components;
use apex\app\web\html_tags;
use apex\core\admin;
use apex\users\user;
use apex\app\interfaces\ViewInterface;
use apex\app\msg\objects\event_message;


/**
 * Template Parser
 *
 * Service: apex\utils\template
 *
 * Handles the parsing of all .tpl template files located within the /views/ 
 * directory.  For more information on templates, please refer to the 
 * developer documentation. 
 * 
* This class is available within the services container, meaning its methods can be accessed statically via the 
 * service singleton as shown below.
 *
 * PHP Example
 * --------------------------------------------------
 * 
 * <?php
 * 
 * namespace apex;
 *
 * use apex\app;
 * use apex\utils\template;
 *
 * view::assign('name', 'John Smith');
 * $html = view::parse();
 *
 */
class view implements ViewInterface
{


    // Injected properties
    private $app;
    private $html_tags;

    // Template properties
    protected $template_path = '';
    protected $path_is_defined = false;
    protected $theme_dir;
    protected $has_errors = false;
    protected $callouts = [];
    protected $page_title;

    // Internal properties
    protected $vars = [];
    protected $js_code;
    protected $tpl_code = '';


/**
 * Constructor
 * 
 * @param app $app The main apex\app object
 * @param html_tags $html_tags The html_tags object
 * @param string $template_path The URI to display 
 */
public function __construct(app $app, html_tags $html_tags, string $template_path = '')
{ 

    // Set variables
    $this->app = $app;
    $this->html_tags = $html_tags;
    $this->path_is_defined = $template_path == '' ? false : true;
    $this->layout_alias = '';

}

/**
 * Initialize template engine 
 *
 * Initializes the template engine, and sets the appropriate route based on 
 * URI from registry. 
 */
public function initialize()
{
 
    // Set variables
    $this->theme_dir = SITE_PATH . '/views/themes/' . app::get_theme();

    // Set template path, if needed
    if ($this->template_path == '') { 
        $this->template_path = app::get_area() == 'public' ? 'public/' . app::get_uri() : app::get_uri();
    }

}

/**
 * Parse a TPL template file 
 *
 * Fully parses a template including all aspects from theme layouts, special 
 * tags, and more. 
 *
 * @return string THe resulting HTML code.
 */
public function parse():string
{ 

    // Load objects
    $this->html_tags = app::make(html_tags::class);

    // Initialize
    $this->initialize();
    debug::add(1, tr("Begin parsing template, /{1}", $this->template_path));

    // Dispatch RPC call
    $this->dispatch_rpc_call();
    debug::add(4, tr("Completed RPC call for template, {1}", $this->template_path));

    // Set template path
    $this->template_path = app::get_area() == 'public' ? 'public/' . app::get_uri() : app::get_uri();

    // Execute any necessary PHP code
    $this->execute_php_file();

    // Get TPL file
    if (check_package('demo') === true && app::get_area() == 'public' && app::get_uri() == 'index' && file_exists(SITE_PATH . '/views/themes/' . app::get_theme() . '/tpl/index.tpl')) { 
        $tpl_file = SITE_PATH . '/views/themes/' . app::get_theme() . '/tpl/index.tpl';
    } else { 
        $tpl_file = SITE_PATH . '/views/tpl/' . $this->template_path . '.tpl';
    }

    // Get tpL code
    if (file_exists($tpl_file)) { 
        $this->tpl_code = file_get_contents($tpl_file);
    } elseif (file_exists(SITE_PATH . '/views/tpl/' . app::get_area() . '/404.tpl')) { 
        $this->tpl_code = file_get_contents(SITE_PATH . '/views/tpl/' . app::get_area() . '/404.tpl');
    } else { 
        return "We're sorry, but no TPL file exists for the location " . app::get_uri() . " within the panel " . app::get_area() . ", and no 404 template was found.";
    }

    // debug
    debug::add(4, tr("Acquired TPL code for template, {1}", $this->template_path));

    // Load base variables
    $this->load_base_variables();
    debug::add(5, tr("Loaded base template variables"));

    // Add layout
    $this->add_layout();
    debug::add(5, tr("Added layout to template, theme: {1}, URI: {2}", app::get_theme(), $this->template_path));

    // Process theme components
    $this->process_theme_components();
    debug::add(5, tr("Completed processing all theme components for template"));

    debug::add(5, "Processed cached assets for template");

    // Parse HTML
    $html = $this->parse_html($this->tpl_code);
    debug::add(4, tr("Successfully parsed HTML for template, {1}", $this->template_path));

    // Add system Javascript / HTML
    $html = $this->add_system_javascript($html);

    // Process page_process() function from theme.php file, if exists
    $html = $this->process_theme_page_function($html);

    // Merge variables
    $html = $this->merge_vars($html);

    // 
    // Debug
    debug::add(1, tr("Successfully parsed template and returning resulting HTML, {1}", $this->template_path));

    // Return
    return $html;

}

/**
 * Parse TPL code 
 *
 * Parses TPL code, and transforms it into HTML code.  This is used for the 
 * body of the TPL file, but also other things such as the resulting TPL code 
 * from HTML functions and tab controls. 
 *
 * @param string $html The TPL code to parse.
 *
 * @return string The resulting HTML code.
 */
public function parse_html(string $html):string
{ 

    // Initialize
    $html_tags = $this->html_tags;

    // Merge vars
    $html = $this->merge_vars($html);

    // Process IF tags
    $html = $this->process_if_tags($html);
    debug::add(5, "Processed template IF tags");

    // Process sections
    $html = $this->process_sections($html);
    debug::add(5, tr("Processed template section tags."));

    // Process HTML functions
    $html = $this->process_function_tags($html);
    debug::add(5, tr("Processed template HTML function tags"));

    // Callouts
    $html = str_ireplace("<a:callouts>", $html_tags->callouts($this->callouts), $html);
    debug::add(5, tr("Processed template callouts"));

    // Process page title
    $html = $this->process_page_title($html);
    debug::add(5, tr("Processed template page title"));

    // Process nav menus
    $html = $this->process_nav_menu($html);
    debug::add(5, tr("Processed template nav menus"));

    // Process a: tags
    preg_match_all("/<a:(.+?)>/si", $html, $tag_match, PREG_SET_ORDER);
    foreach ($tag_match as $match) { 
        $tag = $match[1];

        // Parse attributes
        $attr = array();
        if (preg_match("/(.+?)\s(.+)$/", $tag, $attr_match)) { 
            $tag = $attr_match[1];
            $attr = $this->parse_attr($attr_match[2]);
        }

        // Check for closing tag
        $chk_match = strtr($match[0], array("/" => "\\/", "'" => "\\'", "\"" => "\\\"", "\$" => "\\\$"));
        if (preg_match("/$chk_match(.*?)<\/a\:$tag>/si", $html, $html_match)) { 
            $text = $html_match[1];
            $match[0] = $html_match[0];
        } else { $text = ''; }

        // Replace HTML tag
        $html = str_replace($match[0], $this->get_html_tag($tag, $attr, $text), $html);
    }


    // Replace special characters
    $html = str_replace(array('~op~','~cp~'), array('(', ')'), $html);

    // Debug
    debug::add(4, tr("Successfully finished parsing TPL code of template."));

    // Return
    return $html;

}

/**
 * Parse .php file of a template.
 *
 */
protected function execute_php_file()
{

    // Get .php filename
    $php_file = SITE_PATH . '/views/php/' . $this->template_path . '.php';
    if (!file_exists($php_file)) { return; }

    // Execute PHP file
    require($php_file);

    // Debug
    debug::add(4, tr("Loaded template PHP file for, {1}", $this->template_path));

    // Grab $config again, in case it was modified
    $this->vars['config'] = app::getall_config();

    // Return, if template path was defined
    if ($this->path_is_defined === true) { return; }

    // Check if URI was changed
    $chk_path = app::get_area() == 'public' ? 'public/' . app::get_uri() : app::get_uri();
    if ($chk_path == $this->template_path) { return; }

    // Execute PHP code again
    $this->template_path = $chk_path;
    $this->execute_php_file();

}

/**
 * Dispatch RPL call, perform actions of other packages. 
 *
 * Dispatch an RPC call, and update template variables accordingly, depending 
 * on what other packages require. 
 */
protected function dispatch_rpc_call()
{ 

    // Return if displaying 504.tpl (RPC timeout / error) template
    if (preg_match("/504$/", app::get_uri())) { 
        return;
    }

    // Send RPC call
    $msg = new event_message('core.template.parse');
    $response = msg::dispatch($msg)->get_response();

    // Parse response, and assign necessary template variables
    foreach ($response as $package => $vars) { 
        if (!is_array($vars)) { continue; }
        foreach ($vars as $key => $value) { 
            $this->assign($key, $value);
        }
    }

}

/**
 * Add layout to TPL file 
 *
 * Overlays a TPL file with the appropriate layout, depending on the template 
 * being displayed, and which theme is being used. 
 */
protected function add_layout()
{ 

    // Check cms_layouts table for layout
    if (redis::hexists('config:db_master', 'dbname') && $value = redis::hget('cms:layouts', $this->template_path)) { 
        $layout = $value;
    } else { $layout = 'default'; }
    $this->layout_alias = $layout;

    // Debug
    debug::add(5, tr("Determined template layout, {1}", $layout));

    // Check if layout exists
    $layout_file = $this->theme_dir . '/layouts/' . $layout . '.tpl';
    if (file_exists($layout_file)) { 
        $layout_html = file_get_contents($layout_file);
    } elseif ($layout != 'default' && file_exists($this->theme_dir . '/layouts/default.tpl')) { 
        $layout_html = file_get_contents($this->theme_dir . '/layouts/default.tpl');
        debug::add(3, tr("Template layout file does not exist, {1}, reverting to default layout", $layout), 'warning');
    } else { 
        debug::add(1, tr("No layout file exists for template, and no default layout.  Returning with no layout"), 'warning');
        return;
    }

    // Replace page contents
    $this->tpl_code = str_replace("<a:page_contents>", $this->tpl_code, $layout_html);

}

/**
 * Get page title 
 *
 * Gets the page title.  Checks the cms_templates mySQL table, and otherwise 
 * looks for a <h1>...</h1> tags in the TPL code, and if non exist, default to 
 * the $config['site_name'] variable. 
 */
protected function get_page_title()
{ 

    //Get page title
    $title = '';
    if ($value = redis::hget('cms:titles', $this->template_path)) { 
        $title = $value;
    } elseif (preg_match("/<h1>(.+?)<\/h1>/i", $this->tpl_code, $match)) { 
        $title = $match[1];
        $this->tpl_code = str_replace($match[0], '', $this->tpl_code);
    } elseif (app::get_http_controller() != 'admin') { 
        $title = app::_config('core:site_name');
    }

    // Debug
    debug::add(5, tr("Retrived template page title, {1}", $title));

/// Return
    return $title;

}

/**
 * Process page title 
 *
 * Processes the page title, and adds it into the correct places within the 
 * template with the proper formatting. 
 *
 * @param string $html The HTML to replace title within.
 *
 * @return string The resulting HTML
 */
protected function process_page_title(string $html):string
{ 

    // Go through e:page_title tags
    preg_match_all("/<a:page_title(.*?)>/si", $html, $tag_match, PREG_SET_ORDER);
    foreach ($tag_match as $match) { 

        $attr = $this->parse_attr($match[1]);
        $html = str_replace($match[0], $this->get_html_tag('page_title', $attr, $this->page_title), $html);
    }

    // Return
    return $html;

}

/**
 * Process nav menu 
 *
 * Parses the <e:nav_menu> as necessary, and replaces with the appropriate 
 * HTML code.  Please refer to documentation for full details. 
 *
 * @param string $html The HTML code to parse.
 *
 * @return string The resulting HTML code.
 */
protected function process_nav_menu(string $html):string
{ 

    // Initial checks
    if (!preg_match("/<a:nav_menu(.*?)>/i", $html, $match)) { 
        return $html;
    }

    // Parse tag HTML
    $tag_header = $this->html_tags->get_tag('nav.header');
    $tag_parent = $this->html_tags->get_tag('nav.parent');
    $tag_menu = $this->html_tags->get_tag('nav.menu');

    // Go through menus
    $menu_html = '';
    $rows = db::query("SELECT * FROM cms_menus WHERE area = %s AND parent = '' ORDER BY order_num", app::get_area());
    foreach ($rows as $row) { 

        // Skip, if needed
        if (app::get_area() == 'public') { 
            if ($row['require_login'] == 1 && app::get_userid() == 0) { continue; }
        if ($row['require_nologin'] == 1 && app::get_userid() > 0) { continue; }
        }

        // Get HTML to use
        if ($row['link_type'] == 'header') { $temp_html = $tag_header; }
        elseif ($row['link_type'] == 'parent') { $temp_html = $tag_parent; }
        else { $temp_html = $tag_menu; }

        // Get child menus
        $submenus = '';
        $crows = db::query("SELECT * FROM cms_menus WHERE area = %s AND parent = %s ORDER BY order_num", app::get_area(), $row['alias']);
        foreach ($crows as $crow) { 

            // Skip, if needed
            if (app::get_area() == 'public') { 
                if ($crow['require_login'] == 1 && app::get_userid() == 0) { continue; }
                if ($crow['require_nologin'] == 1 && app::get_userid() > 0) { continue; }
            }

            $submenus .= self::process_menu_row($tag_menu, $crow);
        }

        // Add to menu HTML
        $menu_html .= self::process_menu_row($temp_html, $row, $submenus);
    }

    // Replace HTML
    $html = str_replace("<a:nav_menu>", $menu_html, $html);

    // Return
    return $html;


}

/**
 * Protected.  Process single nav menu row. 
 *
 * Protected function that processes a single row from the 'cms_menus' table, 
 * and returns the appropriate HTML for that single menu item. 
 *
 * @param string $html The HTML code to use for the menu.
 * @param array $row The row from the 'cms-_menus' database table.
 * @param string $submenus The HTML code for any sub menus.
 *
 * @return string The resulting HTML code.
 */
protected function process_menu_row(string $html, array $row, string $submenus = ''):string
{ 

    // Get URL
    if ($row['link_type'] == 'parent') { $url = '#'; }
    elseif ($row['link_type'] == 'external') { $url = $row['url']; }
    elseif ($row['link_type'] == 'internal') { 
        $url = '';
        if (app::get_area() != 'public') { $url .= '/' . app::get_area(); }
        if ($row['parent'] != '') { $url .= '/' . $row['parent']; }
        $url .= '/' . trim($row['alias'] . '/');

    } else { $url = ''; }

    // Merge HTML
    $icon = $row['icon'] == '' ? '' : '<i class="' . $row['icon'] . '"></i>';
    $html = str_replace("~url~", $url, $html);
    $html = str_replace("~icon~", $icon, $html);
    $html = str_replace("~name~", tr($row['name']), $html);
    $html = str_replace("~submenus~", $submenus, $html);

    // Return
    return $html;

}

/**
 * Process function HTML tags 
 *
 * Processes all the <e:function> tags within the TPL code, and replaces them 
 *
 * @param string $html The HTML code to process.
 *
 * @return string The resulting HTML code.
 */
protected function process_function_tags(string $html):string
{ 

    // Go through function tags
    preg_match_all("/<a:function (.*?)>/si", $html, $tag_match, PREG_SET_ORDER);
    foreach ($tag_match as $match) { 

        // Parse attributes
        $attr = $this->parse_attr($match[1]);
        if (!isset($attr['alias'])) { 
            $html = str_replace($match[0], "<b>ERROR:</b. No 'alias' attribute exists within the 'function' tag, which is required.", $html);
            debug::add(3, tr("Template encountered a e:function tag without an 'alias' attribute"), 'notice');
            continue;
        }

        // Get package and alias
        if (!list($package, $parent, $alias) = components::check('htmlfunc', $attr['alias'])) { 
            $html = str_replace($match[0], "The HTML function '$attr[alias]' either does not exists, or exists in more than one package and no specific package was defined.", $html);
            debug::add(1, tr("Template contains invalid e:function tag, the HTML function does not exist, package: {1}, alias: {2}", $package, $alias), 'notice');
            continue;
        }

        // Get temp HTML
        $func_tpl_file = SITE_PATH . '/' . components::get_tpl_file('htmlfunc', $alias, $package);
        $temp_html = file_exists($func_tpl_file) ? file_get_contents($func_tpl_file) : '';

        // Call HTML function
        if (!$response = components::call('process', 'htmlfunc', $alias, $package, '', array('html' => $temp_html, 'data' => $attr))) { 
            $html = str_replace($match[0], "<b>ERROR:</b> Unable to load html function with alias '$alias' from package '$package'", $html);
            debug::add(1, tr("Parsing e:function tag within TPL code resulted in 'htmlfunc' component that could not be loaded, package: {1}, alias: {2}", $package, $alias), 'error');
            continue;
        }

        // Replace HTML
        $html = str_replace($match[0], $this->parse_html($response), $html);
        debug::add(5, tr("Successfully processed e:function tag within TPL code, package: {1}, alias: {2}", $package, $alias));
    }

    // Return
    return $html;

}

/**
 * Process IF tags 
 *
 * Parses all the <e:if> tags within the TPL code, and returns the appropriate HTML
 *
 * @param string $html The HTML code to process.
 *
 * @return string The resulting HTML code.
 */
protected function process_if_tags(string $html):string
{ 

    // Go through all IF tags
    preg_match_all("/<a:if (.*?)>(.*?)<\/a:if>/si", $html, $tag_match, PREG_SET_ORDER);
    foreach ($tag_match as $match) { 

        // Check for <eLelse> tag
        if (preg_match("/^(.*?)<a:else>(.*)$/si", $match[2], $else_match)) { 
            $if_html = $else_match[1];
            $else_html = $else_match[2];
        } else { 
            $if_html = $match[2];
            $else_html = '';
        }

        // Check condition
        debug::add(5, tr("Template, checking IF condition: {1}", $match[1]));
        $replace_html = eval( "return " . $match[1] . ";" ) === true ? $if_html : $else_html;
        $html = str_replace($match[0], $replace_html, $html);
    }

    // Return
    return $html;

}

/**
 * Process section tags 
 *
 * Processes all the <e:section> tags found within the TPL code, which loop 
 * over an array copying the HTML in between the tags for each set of data. 
 *
 * @param string $html The HTML code to process.
 *
 * @return string The resulting HTML code.
 */
protected function process_sections(string $html):string
{ 

    // Go through sections
    preg_match_all("/<a:section(.*?)>(.*?)<\/a:section>/si", $html, $tag_match, PREG_SET_ORDER);
    foreach ($tag_match as $match) { 

        // Parse attributes
        $attr = $this->parse_attr($match[1]);

        // Check if variable exists
        if (!isset($this->vars[$attr['name']])) { 
            $html = str_replace($match[0], "", $html);
            debug::add(2, tr("Template encountered a e:ection tag without a 'name' attribute.  Could not parse in template, /{1}/{2}", app::get_area(), app::get_uri()), 'error');
            continue;
        }

        // Debug
        debug::add(5, tr("Processing template e:section tag with name '{1}'", $attr['name']));

        // Get replacement HTML
        $replace_html = '';
        foreach ($this->vars[$attr['name']] as $vars) { 
            $temp_html = $match[2];

            // Replace
            foreach ($vars as $key => $value) { 
        if (is_array($value)) { continue; }
                $key = $attr['name'] . '.' . $key;
                $temp_html = str_ireplace("~$key~", $value, $temp_html);
            }
            $replace_html .= $temp_html;
        }

        // Replace in HTML
        $html = str_replace($match[0], $replace_html, $html);
    }


    // Return
return $html;

}

/**
 * Process a:theme tags 
 *
 * Parses all the <e:theme> tags within the TPL code, and replaces them with 
 * the correct contents for the section within the THEME_DIR/sections/ 
 * directory. 
 */
protected function process_theme_components()
{ 

    // Get Javascript code
    preg_match_all("/<script(.*?)>(.*?)<\/script>/si", $this->tpl_code, $js_match, PREG_SET_ORDER);
    foreach ($js_match as $match) { 
        $this->js_code .= "\n" . $match[2] . "\n";
        $this->tpl_code = str_replace($match[0], '', $this->tpl_code);
    }

    // Go through theme components
    while (preg_match("/<a:theme(.*?)>/si", $this->tpl_code)) { 

        preg_match_all("/<a:theme(.*?)>/si", $this->tpl_code, $theme_match, PREG_SET_ORDER);
        foreach ($theme_match as $match) { 

            // Parse attributes
            $attr = $this->parse_attr($match[1]);

            // Section file
            if (isset($attr['section']) && $attr['section'] != '') { 
                $temp_html = file_exists($this->theme_dir . '/sections/' . $attr['section']) ? file_get_contents($this->theme_dir . '/sections/' . $attr['section']) : "<b>ERROR: Theme section file does not exist, $attr[section].</b>";
            } else { 
                $temp_html = "<b>ERROR: Invalid theme tag.  No valid attributes found.</b>";
            }
            $this->tpl_code = str_replace($match[0], $temp_html, $this->tpl_code);

        }

    }
}

/**
 * Protected. Assign base variables 
 *
 * Assigns the base variables that are available to all templates, such as the 
 * URI to the theme directrory, the ID# of the auhenticated user, and so on. 
 */
public function load_base_variables()
{ 

    // Set theme directory, in case it changed via RPC call
    $this->theme_dir = SITE_PATH . '/views/themes/' . app::get_theme();

// Set base variables
    $this->assign('theme_uri', '/themes/' . app::get_theme());
    $this->assign('current_year', date('Y'));
    $this->assign('config', app::getall_config());
    $this->assign('userid', app::get_userid());
    $this->assign('uri', app::get_uri());

    // Get page title
    $this->page_title = $this->get_page_title();

    // Check unread unalerts
    $recipient = 'user:' . app::get_userid();
    if (app::get_userid() == 0 || !$unread_alerts = redis::hget('unread:alerts', $recipient)) { 
        $unread_alerts = '0';
        $display_unread_alerts = 'none';
    } else { $display_unread_alerts = ''; }

    // Check unread messages
    if (app::get_userid() == 0 || !$unread_messages = redis::hget('unread:messages', $recipient)) { 
        $unread_messages = 0;
        $display_unread_messages = 'none';
    } else { $display_unread_messages = ''; }

    // Assign variables for unread alerts / messages
    $this->assign('unread_alerts', $unread_alerts);
    $this->assign('unread_messages', $unread_messages);
    $this->assign('display_unread_alerts', $display_unread_alerts);
    $this->assign('display_unread_messages', $display_unread_messages);

    // Get profile
    if (app::get_userid() > 0) { 

        $ok = true;
        if (app::get_area() != 'admin' && !redis::exists('user:' . app::get_userid())) { $ok = false; }
        if ($ok === true) { 

            $user_class = app::get_area() == 'admin' ? admin::class : user::class;
            $profile = app::make($user_class, ['id' => app::get_userid()])->load();
            $this->assign('profile', $profile);

            // Go through profile fields
            foreach ($profile as $key => $value) { 
                view::assign($key, $value);
            }
        }
    }

    // Set site variables
    $site_vars = array(
        'domain_name' => app::_config('core:domain_name'), 
        'name' => app::_config('core:site_name'), 
        'address' => app::_config('core:site_address'), 
        'address2' => app::_config('core:site_address2'), 
        'email' => app::_config('core:site_email'), 
        'phone' => app::_config('core:site)phone'), 
        'tagline' => app::_config('core:site_tagline'),
        'about_us' => app::_config('core:site_about_us'),  
        'facebook' => app::_config('core:site_facebook'), 
        'twitter' => app::_config('core:site_twitter'), 
        'linkedin' => app::_config('core:site_linkedin'), 
        'youtube' => app::_config('core:site_youtube'), 
        'reddit' => app::_config('core:site_reddit'), 
        'instagram' => app::_config('core:site_instagram')
    );
    $this->assign('site', $site_vars);

}

/**
 * Merge assigned variables 
 *
 * Goes through the self::$vars array, which is populated via the 
 * self::assign() method, and replaces all occurences of ~key~ with its 
 * corresponding value within the TPL code. Fully supports arrays with 
 * ~arrayname.key~ merge fields in the TPL code. 
 *
 * @param string $html The HTML code to process.
 *
 * @return string The resulting HTML code.
 */
protected function merge_vars(string $html):string
{ 

    foreach ($this->vars as $key => $value) { 

        if (is_array($value)) { 
            foreach ($value as $skey => $svalue) { 
                if (is_array($svalue) || is_object($svalue)) { continue; }
                $html = str_replace('~' . $key . '.' . $skey . '~', $svalue, $html);
            }

        } else { 
            $html = str_ireplace("~$key~", $value, $html);
        }
    }

    // Return
    return $html;


}

/**
 * Get HTML code for all <a:> tags 
 *
 * Retrives the correct HTML code for any other special e: tag. Generally goes 
 * through the /lib/html_tags.php class, unless a specific method exists for 
 * this theme. 
 *
 * @param string $tag The name of the HTML tag.
 * @param array $attr The attributes specified within the HTML tag.
 * @param string $text Any text in between opening and closing tags.
 *
 * @return string The resulting HTML code.
 */
private function get_html_tag(string $tag, array $attr, string $text = ''):string
{

    // Return HTML
    return $this->html_tags->$tag($attr, $text);

}

/**
 * Get attributes of HTML tag 
 *
 * Simply parses the attributes of any given HTML tag, and returns them in an 
 * array. 
 *
 * @param string $string The string of text to process.
 *
 * @return array The attributes contained within passed string.
 */
public function parse_attr(string $string):array
{ 

    // Parse string
    $attributes = array();
    preg_match_all("/(\w+?)\=\"(.*?)\"/", $string, $attribute_match, PREG_SET_ORDER);
    foreach ($attribute_match as $match) { 
        $value = str_replace("\"", "", $match[2]);
        $attributes[$match[1]] = $value;
    }

    // Return
    return $attributes;

}

/**
 * Assign variable 
 *
 * Assigns a variable which is then later replaced by the corresponding merge 
 * field within the TPL code.  Merge fields are surrounded by tilda marks (eg. 
 * ~name~). ( Supports arrays as well, and values can be access via merge 
 * fields with ~arrayname.variable~ 
 *
 * @param string $name The name of the merge variable.
 * @param mixed $value The value of the merge variable -- string or array. 
 */
public function assign(string $name, $value)
{ 

    // Check for array
    if (is_array($value)) { 
        foreach ($value as $k => $v) { 
        if (is_array($k) || is_array($v)) { continue; }
        if (is_object($k) || is_object($v)) { continue; }

            $value[$k] = (string) $v;
        }
        $this->vars[$name] = $value;
    } else { 
        $this->vars[$name] = (string) $value;
    }

    // Add to event queue, if inside worker
    if (app::get_reqtype() == 'worker') { 
        app::add_event('view_assign', array($name, $value));
    } 


    // Debug
    debug::add(5, tr("Assigned template variable {1} to {2}", $name, $value));

}

/**
 * Add callout message 
 *
 * Adds a callout message, which is then displayed at the top of the template 
 *
 * @param string $message The message to add
 * @param string $type Optional type, defaults to 'success', but can also be either 'error' or 'info'
 */
public function add_callout(string $message, string $type = 'success')
{ 

    // Add message to needed array
    if (!isset($this->callouts[$type])) { $this->callouts[$type] = array(); }
    array_push($this->callouts[$type], $message);
    if ($type == 'error') { $this->has_errors = true; }

    // Add to event queue, if inside worker
    if (app::get_reqtype() == 'worker') { 
        app::add_event('view_callout', array($message, $type));
    } 

    // Return
    return true;
}

/**
 * Add Javascript ( Adds Javascript, which is later included just above the 
 * </body> tag upon processing.  Rarely used. 
 *
 * @param string $js The Javascript to add.
 */
public function add_javascript(string $js)
{ 
    $this->js_code .= "\n$js\n";
}

/**
 * ( Replace TPL file with system Javascript 
 *
 * Add system Javascript 
 *
 * @param string $html The HTML code to process.
 */
protected function add_system_javascript($html)
{ 

    // Add custom CSS, as needed
    $html = str_replace("<body>", base64_decode('CjxzdHlsZSB0eXBlPSJ0ZXh0L2NzcyI+CgogICAgLmZvcm1fdGFibGUgeyBtYXJnaW4tbGVmdDogMjVweDsgfQogICAgLmZvcm1fdGFibGUgdGQgewogICAgICAgIHRleHQtYWxpZ246IGxlZnQ7CiAgICAgICAgdmVydGljYWwtYWxpZ246IHRvcDsKICAgICAgICBwYWRkaW5nOiA4cHg7CiAgICB9Cgo8L3N0eWxlPgoK') . "\n<body>", $html);

    // Check if Javascript disabled
    if (app::_config('core:enable_javascript') == 0) { 
        return $html;
    }

    // Add any Javascript in memory
    if ($this->js_code != '') { 
        $html = str_replace("</body>", "\t<script type=\"text/javascript\">\n" . $this->js_code . "\n\t</script>\n\n</body>", $html);
    }

    // Get WS auth hash
    if (app::get_userid() > 0) { 
        $ws_auth = implode(":", array(app::get_area(), app::get_uri(), auth::get_hash()));
    } else { 
        $ws_auth = implode(":", array(app::get_area(), app::get_uri(), 'public', (time() . rand(0, 99999))));
    }

    // Add WebSocket connection to Javascript
    $host = redis::hget('config:rabbitmq', 'host') . ':' . app::_config('core:websocket_port');
    $js = "\t<script type=\"text/javascript\">\n";
    $js .= "\t\tvar ws_conn = new WebSocket('ws://" . $host . "');\n";
    $js .= "\t\tws_conn.onopen = function(e) {\n";
    $js .= "\t\t\tws_conn.send(\"ApexAuth: $ws_auth\");\n";
    $js .= "\t\t}\n";
    $js .= "\t\tws_conn.onmessage = function(e) {\n";
    $js .= "\t\t\tvar json = JSON.parse(e.data);\n";
    $js .= "\t\t\tajax_response(json);\n";
    $js .= "\t\t}\n";
    $js .= "\t</script>\n\n";

    // Set Apex Javascript
    $js .= "\t" . '<script type="text/javascript" src="/plugins/apex.js"></script>' . "\n";
    $js .= "\t" . '<script src="/plugins/parsley.js/parsley.min.js" type="text/javascript"></script>' . "\n";
    $js .= "\t" . '<script src="https://www.google.com/recaptcha/api.js"></script>' . "\n\n";
    $js .= "</head>\n\n";

    // Add to HTML
    $html = str_replace("</head>", $js, $html);
    $html = str_replace("</body>", base64_decode('CjxkaXYgaWQ9ImFwZXhfbW9kYWwiIGNsYXNzPSJtb2RhbCBmYWRlIiByb2xlPSJkaWFsb2ciPjxkaXYgY2xhc3M9Im1vZGFsLWRpYWxvZyI+CiAgICA8ZGl2IGNsYXNzPSJtb2RhbC1jb250ZW50Ij4KCiAgICAgICAgPGRpdiBjbGFzcz0ibW9kYWwtaGVhZGVyIj4KICAgICAgICAgICAgPGJ1dHRvbiB0eXBlPSJidXR0b24iIGNsYXNzPSJjbG9zZSIgb25jbGljaz0iY2xvc2VfbW9kYWwoKTsiPiZ0aW1lczs8L2J1dHRvbj4KICAgICAgICAgICAgPGg0IGNsYXNzPSJtb2RhbC10aXRsZSIgaWQ9ImFwZXhfbW9kYWwtdGl0bGUiPjwvaDQ+CiAgICAgICAgPC9kaXY+CiAgICAgICAgPGRpdiBDbGFzcz0ibW9kYWwtYm9keSIgaWQ9ImFwZXhfbW9kYWwtYm9keSI+PC9kaXY+CiAgICAgICAgPGRpdiBjbGFzcz0ibW9kYWwtZm9vdGVyIj4KICAgICAgICAgICAgPGJ1dHRvbiB0eXBlPSJidXR0b24iIGNsYXNzPSJidG4gYnRuLWRlZmF1bHQiIG9uY2xpY2s9ImNsb3NlX21vZGFsKCk7Ij5DbG9zZTwvYnV0dG9uPgogICAgICAgIDwvZGl2PgogICAgPC9kaXY+CjwvZGl2PjwvZGl2PgoKCg==') . "</body>", $html);

    // Return
    return $html;

}

/**
 * Process process_page() function from theme.php file
 *
 * @param string $html The current HTML code of the page
 *
 * @return string The resulting HTML code
 */
protected function process_theme_page_function(string $html):string
{

    // Check if theme.php file exists
    if (!file_exists($this->theme_dir . '/theme.php')) { 
        return $html;
    }

    // Load theme file
    require_once($this->theme_dir . '/theme.php');
    $class_name = 'theme_' . app::get_theme();
    $client = new $class_name();

    // Check if function exists
    if (!method_exists($client, 'process_page')) { 
        return $html;
    }

    // Execute function and return
    $html = $client->process_page($html, $this->layout_alias);
    return $html;

}


/**
 * Check for user errors. 
 *
 * Returns a boolean whether or not the current template has any callouts of 
 * the 'error' type, meaning there are user generated errors. 
 */
public function has_errors():bool { return $this->has_errors; }

/**
 * Get the page title 
 *
 * @return string The current page title
 */
public function get_title():string { return $this->page_title; }

/**
 * Get all callouts 
 *
 * @return array Array of all callouts currently added to the template.
 */
public function get_callouts():array { return $this->callouts; }

/**
 * Reset, and get ready for another request. 
 */
public function reset()
{ 
    $this->has_errors = false;
    $this->template_path = '';
    $this->callouts = [];
}


}

