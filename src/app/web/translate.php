<?php
declare(strict_types = 1);

namespace apex\core;

use apex\app;
use apex\libc\db;
use apex\app\sys\components;


/**
 * Handles all functionality for tranlsation / language packs, including 
 * parsing all TPL / PHP files and extracting the necessary engish words / 
 * pharses from them, generating the language packs, and more. 
 */
class translate
{




/**
 * Add a new translation word / phrase 
 *
 * Adds a string of text to the internal_transactions table with the proper 
 * hash, which can then be translated into other languages. 
 *
 * @param string $text The text to add as a hash, and ready for translation.
 * @param string $type Helps differentiate the text strings.  Generally always either 'system', 'admin', 'members', or 'public'.
 * @param bool Whether or not the operation was successful.
 */
function add_hash(string $text, string $type = 'system'):bool
{ 

    // Check if exists
    $md5hash = md5($text);
    if ($row = db::get_row("SELECT * FROM internal_translations WHERE language = 'en' AND md5hash = %s", $md5hash)) { 

        // Check if type should be updated
        $update_type = false;
        if ($row['type'] != 'public' && $type == 'public') { $update_type = false; }
        elseif ($row['type'] == 'admin' && $type == 'members') { $update_type = true; }

        // Update type, if needed
        if ($update_type === true) { 
            db::query("UPDATE internal_translations SET type = %s WHERE id = %i", $type, $row['id']);
        }

        // Return
        return true;
    }

    // Add to DB
    db::insert('internal_translations', array(
        'language' => 'en',
        'type' => $type,
        'md5hash' => $md5hash,
        'contents' => base64_encode($text))
    );

    // Return
    return true;

}

/**
 * / *Compile language pack of the system. 
 *
 * Goes through all templates and PHP files, pulls out the text, and ensures a 
 * MD5 hash of each string is within the 'internal_transactions' table, which 
 * is then used to translate to other languages. 
 */
public function compile_language_pack()
{ 

    // Go through CMS menus
    $rows = db::query("SELECT area,display_name FROM cms_menus");
    foreach ($rows as $row) { 
        self::add_hash($row['display_name'], $row['area']);
    }

    // Go through forms
    $rows = db::query("SELECT package,alias FROM internal_components WHERE type = 'form'");
    foreach ($rows as $row) { 

        // Load form
        if (!$form = components::load('form', $row['alias'], $row['package'])) { continue; }

        // Go through form fields
        $Form_fields = $form->get_fields();
        foreach ($form_fields as $alias => $vars) { 
            if (isset($vars['label'])) { self::add_hash($vars['label'], 'system'); }
            if (isset($vars['placeholder'])) { self::add_hash($vars['placeholder']); }
            if (isset($vars['name']) && !isset($vars['label'])) { self::add_hash(ucwords(str_replace("_", " ", $vars['name'])), 'system'); }
        }
    }

    // Go through table columns
    $rows = db::query("SELECT package,alias FROM internal_components WHERE type = 'table'");
    foreach ($rows as $row) { 

        // Load table
        if (!$table = components::load('table', $row['alias'], $row['package'])) { continue; }

        // Go through columns
        foreach ($table::$columns as $alias => $name) { 
            if ($name == '' || $name == "&nbsp;") { continue; }
            self::add_hash($name, 'system');
        }
    }

    // Go through tab controls
    $rows = db::query("SELECT package,alias FROM internal_components WHERE type = 'tabcontrol'");
    foreach ($rows as $row) { 

        // Load tab control
        if (!$tab = components::load('tabcontrol', $row['alias'], $row['package'])) { continue; }

        // Go through tab pages
        foreach ($tab::$tabpages as $alias => $name) { 
            self::add_hash($name, 'system');
        }

        // Go through tab pages
        $pages = db::get_column("SELECT alias FROM internal_components WHERE type = 'tabpage' AND parent = %s", $row['alias']);
        foreach ($pages as $alias) { 
            if (in_array($alias, $tab->tabpages)) { continue; }

            // Load page
            if (!$page = components::load('tabpage', $alias, $row['package'], $row['parent'])) { continue; }
            if (isset($page::$name)) { self::add_hash($page::$name); }
        }
    }

    // Month names
    for ($x=1; $x <= 12; $x++) { 
        $name = date('F', mktime(0, 0, 0, $x, 1, 2000));
        self::add_hash($name, 'system');
    }

    // Go through all components
    $rows = db::query("SELECT * FROM internal_components");
        foreach ($rows as $row) { 

        // Get PHP file
        $php_file = components::get_file($row['type'], $row['alias'], $row['package'], $row['parent']);
        if ($Php_file != '' && file_exists(SITE_PATH . '/' . $php_file)) { 
            self::parse_phpfile($php_file);
        }

        // Check TPL file
        $tpl_file = components::get_tpl_file($row['type'], $row['alias'], $row['package'], $row['parent']);
        if ($tpl_file != '' && file_exists(SITE_PATH . '/' . $tpl_file)) { 
            self::parse_tplfile($tpl_file);
        }
    }

    // Go thought all packages
    $packages = db::get_column("SELECT alias FROM internal_packages");
    foreach ($packages as $pkg_alias) { 

        // Load package file
        $client = new package($pkg_alias);
        $pkg = $client->load();

        // Go through boxlists
        foreach ($pkg->boxlists as $vars) { 
            self::add_hash($vars['title'], 'admin');
            self::add_hash($vars['description'], 'admin');
        }

        // Hashes
        foreach ($pkg->hash as $hash_alias => $vars) { 
            foreach ($vars as $key => $value) { self::add_hash($value, 'system'); }
        }

        // Parse external files
        foreach ($pkg->ext_files as $file) { 

            if (preg_match("/^(.+)\*$/", $file, $match)) { 

                $files = io::parse_dir(SITE_PATH . '/' . $match[1]);
                foreach ($files as $subfile) { 
                    if (!preg_match("/\.php$/", $subfile)) { continue; }
                    self::parse_phpfile($file . '/' . $subfile);
                }

            } ELSE { 
                if (!preg_match("/\.php$/", $file)) { continue; }
                self::parse_phpfile($file);
            }
        }
    }

}

/**
 * Parse TPL file for translation 
 *
 * Parses a TPL template file, extracts the necessary English text, and 
 * ensures it's within the 'internal_transactions' table with a MD5 hash, to 
 * be used for translation into multiple languages. 
 *
 * @param string $tpl_file Location of the TPL file, relative to the / installation directory.
 */
protected static function parse_tplfile(string $tpl_file)
{ 

    // Get code
    $tpl_code = file_get_contents(SITE_PATH . '/' . $tpl_file);

    // Get type of template
    if (preg_match("/tpl\/admin/", $tpl_file)) { $type = 'admin'; }
    elseif (preg_match("/tpl\/members/", $tpl_file)) { $type = 'members'; }
    else { $type = 'public'; }

    // Set tags
    $tags = array('h1','h2','h3','h4','h5','h6','p','td','th','blockquote');

    // Go through tags
    foreach ($tags as $tag) { 

        preg_match_all("/<$tag(.*?)>(.*?)<\/$tag>/si", $tpl_code, $tag_match, PREG_SET_ORDER);
        foreach ($tag_match as $match) { 
            $words = preg_split("/<(.*?)>/", $match[2]);
            foreach ($words as $word) { 
                if ($word == '') { continue; }
                add_tr_hash($word, $type);
            }
        }
    }

    // Check e:tab_page tags
preg_match_all("/<e\:tab_page(.*?)>/si", $tpl_code, $tag_match, PREG_SET_ORDER);
    foreach ($tag_match as $match) { 

        if (preg_match("/name=\"(.+?\"/", $match[1], $nmatch)) { 
            add_tr_hash($nmatch[1], $type);
        }
    }

    // Go through label="" attributes
    preg_match_all("/label=\"(.+?)\"/si", $tpl_code, $tag_match, PREG_SET_ORDER);
    foreach ($tag_match as $match) { 
        add_tr_hash($match[1], $type);
    }

    // Go through placeholder="" attributes
    preg_match_all("/placeholder=\"(.+?)\"/si", $tpl_code, $tag_match, PREG_SET_ORDER);
    foreach ($tag_match as $match) { 
        add_tr_hash($match[1], $type);
    }

}

/**
 * Parses a PHP file for translation. 
 *
 * Extracts all necessary English text from tr(), add_message(), and 
 * trigger_error() functions, and ensures it's in the 'internal_transactions' 
 * table with a MD5 hash, to be used for translation into multiple languages. 
 *
 * @param string $php_file Location of the PHP file relative to the / installation directory.
 */
protected static function parse_phpfile(string $php_file)
{ 

    // Get code
    $php_code = file_get_contents(SITE_PATH . '/' . $php_file);

    // Parse
    preg_match_all("/ tr\((.)(.*?)\)/s", $php_code, $tr_match, PREG_SET_ORDER);
    foreach ($tr_match as $match) { 
        if ($match[1] == '$') { continue; }
        if ($match[1] != "'" && $match[1] != '"') { continue; }

        // Get message
        if (!preg_match("/^(.*?)\\" . $match[1] . "/", $match[2], $msg_match)) { continue; }
        add_tr_hash($msg_match[1], 'system');
    }

}


}

