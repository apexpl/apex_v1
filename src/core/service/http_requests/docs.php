<?php
declare(strict_types = 1);

namespace apex\core\service\http_requests;

use apex\app;
use apex\libc\view;
use apex\core\service\http_requests;
use michelf\markdown;
use Michelf\MarkdownExtra;


/**
 * Handles the documentation HTTP requests -- the /docs/ directory.  Parses 
 * the necessary .md files via the php-markdown package, and displays it to 
 * the web browser. 
 */
class docs extends http_requests
{

    private $api_classes = [
        app::class => 'app'
    ];

    private $replacements = [
        'tr' => ['app.sys.functions', 'tr', 'files/'],
        'fdate' => ['app.sys.functions', 'fdate', 'files/'],   
        'fmoney' => ['app.sys.functions', 'fmoney', 'files/'], 
        'exchange_money' => ['app.sys.functions', 'exchange_money', 'files/'],
        'fexchange' => ['app.sys.functions', 'fexchange', 'files/'],  
        'check_package' => ['app.sys.functions', 'check_package', 'files/'], 
        'assign' => ['app.web.view', 'assign'], 
        'add_callout' => ['app.web.view', 'add_callout'], 
        'parse' => ['app.web.view', 'parse'], 
        'authenticate_2fa' => ['app.sys.auth', 'authenticate_2fa'], 
        'authenticate_2fa_email' => ['app.sys.auth', 'authenticate_2fa_email'], 
        'authenticate_2fa_sms' => ['app.sys.auth', 'authenticate_2fa_sms'], 
        'recaptcha' => ['app.sys.auth', 'recaptcha'], 
        'dispatch_notification' => ['app.msg.alerts', 'dispatch_notification'], 
        'dispatch_message' => ['app.msg.alerts', 'dispatch_message'], 
        'emailer::send' => ['app.msg.emailer', 'send'], 
        'emailer::process_emails' => ['app.msg.emailer', 'process_emails'], 
        'db::query', ['app.db.mysql', 'query'], 
        'db::insert' => ['app.db.mysql', 'insert'], 
        'db::update' => ['app.db.mysql', 'update'], 
        'db::delete' => ['app.db.mysql', 'delete'], 
        'db::get_row' => ['app.db.mysql', 'get_row'], 
        'db::get_hash' => ['app.db.mysql', 'get_hash'], 
        'db::get_column' => ['app.db.mysql', 'get_column'], 
        'db::get_field' => ['app.db.mysql', 'get_field'], 
        'db::get_idrow' => ['app.db.mysql', 'get_idrow'], 
        'db::insert_id' => ['app.db.mysql', 'insert_id'], 
        'db::show_tables' => ['app.db.mysql', 'show_tables'], 
        'db::show_columns' => ['app.db.mysql', 'show_columns'], 
        'db::begin_transaction' => ['app.db.mysql', 'begin_transaction'], 
        'db::commit' => ['app.db.mysql', 'commit'], 
        'db::rollback' => ['app.db.mysql', 'rollback'], 
        'debug::add' => ['app.sys.debug', 'add'], 
        "\\\$this->http_request" => ['app.tests.tst', 'http_request'], 
        "\\\$this->invoke_method" => ['app.tests.test', 'invoke_method'], 
        'emailer::search_queue' => ['app.tests.test_emailer', 'search_queue'], 
        'emailer::get_queue' => ['app.tests.test_emailer', 'get_queue'], 
        'emailer::clear_queue' => ['app.tests.test_emailer', 'clear_queue']
    ];



/**
 * View page within documentation 
 *
 * Displays the .md documentation files found within the /docs/ directory. 
 * Filters them through the php-markdown package developed my michelf. 
 */
public function process()
{ 

    // Set area / theme
    $theme = app::_config('core:theme_public');
    app::set_area('public');
    app::set_theme($theme);

    // Get URI
    $md_file = preg_replace("/\.md$/", "", implode("/", app::get_uri_segments()));
    if ($md_file == '') { $md_file = 'index'; }
    $md_file .= '.md';

    // Check if .md file exists
    if (!file_exists(SITE_PATH . '/docs/' . $md_file)) { 
        echo "No .md file exists here.";
        exit;
    }

    // Go through API classes
    foreach ($this->api_classes as $class => $api_name) { 
        if (!$obj = app::get($class)) { continue; }
        $methods = get_class_methods($obj);

        foreach ($methods as $method) { 

            if ($class == app::class && in_array($method, array('get','hash','set','make','call'))) { 
                $this->replacements[$method] = ['app.sys.container', $method];
            } else { 
                $this->replacements[$method] = [$api_name];
            }
        }
    }
    unset($this->replacements['__construct']);

    // Get MD template
    $lines = file(SITE_PATH . '/docs/' . $md_file);
    $md_code = '';
    $in_code = false;

    // Go through lines
    foreach ($lines as $line) { 

        // Check if in code
        if (preg_match("/^~~~/", $line)) { 
            $in_code = $in_code === true ? false : true;
            $md_code .= $line;
            continue; 
        } elseif ($in_code === true) { 
            $md_code .= $line;
            continue;
        }

        // Remove <api> tags
        $line = preg_replace("/<api(.+?)>/", "", $line);
        $line = str_replace('</api>', '', $line);

        // Go through replacements
        foreach ($this->replacements as $key => $dest) { 
            preg_match_all("/$key\((.*?)\)/", $line, $tag_match, PREG_SET_ORDER);
            foreach ($tag_match as $match) { 

                // Get method name
                $method = $dest[1] ?? $key;
                if (preg_match("/^(\w+?)\:\:(.+)/", $method, $tmp_match)) { $method = $tmp_match[2]; }

                // Replace
                $uri_type = $dest[2] ?? 'classes/apex.';
                $url = "https://apex-platform.org/api/" . $uri_type . $dest[0] . ".html#method_" . $method;
                $a_code = "<a href=\"$url\" target=\"_blank\">$match[0]</a>";
                $line = str_replace($match[0], $a_code, $line);
            }
        }

        // Add line to md code
        $md_code .= $line;
    }

    // Replace <api: ...> tags
    preg_match_all("/<api:(.*?)>(.*?)<\/api>/", $md_code, $tag_match, PREG_SET_ORDER);
    foreach ($tag_match as $match) { 
        $md_code = str_replace($match[0], '', $md_code); continue; 
    }



    // Apply markdown formatting
    $page_contents = MarkdownExtra::defaultTransform($md_code);
    $page_contents = preg_replace("/<code (.*?)>/", "<code class=\"prettyprint\">", $page_contents);

    // Get HTML
    $theme_dir = SITE_PATH . '/views/themes/' . app::_config('core:theme_public');
    $html = file_get_contents("$theme_dir/layouts/default.tpl");

    // Go through theme sections
    preg_match_all("/<a:theme section=\"(.+?)\">/i", $html, $theme_match, PREG_SET_ORDER);
    foreach ($theme_match as $match) { 
        $temp_html = file_get_contents("$theme_dir/sections/$match[1]");
        $html = str_replace($match[0], $temp_html, $html);
    }
    $html = str_replace("<a:page_contents>", "<<<page_contents>>>", $html);

    // Parse HTML
    view::load_base_variables();
    $html = view::parse_html($html);

    // Display
    $html = preg_replace("/<h1>(.+?)<\/h1>/", "", $html, 1);
    echo str_replace("<<<page_contents>>>", $page_contents, $html);
    exit(0);

}


}

