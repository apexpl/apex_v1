<?php
declare(strict_types = 1);

namespace apex\core\htmlfunc;

use apex\app;
use apex\libc\view;
use apex\app\sys\components;
use apex\app\utils\tables;


class display_table
{




/**
 * Process the HTML function. 
 *
 * Replaces the calling <a:function> tag with the contents generated by this 
 * HTML function. 
 *
 * @param components $components The /app/sys/components.php class.  Injected
 * @param tables $utils The /app/utils/tables.php class.  Injected.
 * @param string $html The contents of the TPL file, if exists, located at /views/htmlfunc/<package>/<alias>.tpl
 * @param array $data The attributes within the calling e:function> tag.
 *
 * @return string The resulting HTML code, which the <e:function> tag within the template is replaced with.
 */
public function process(components $components, tables $utils, string $html = '', array $data):string
{ 

    // Perform checks
    if (!isset($data['table'])) { return "<b>ERROR:</b> No 'table' attribute exists within the e:function tag to display a data table."; }

    // Get package / alias
    if (!list($package, $parent, $alias) = $components->check('table', $data['table'])) { 
        return "<b>ERROR:</b> The table '$data[table]' either does not exist, or no package was specified and it exists in more than one package.";
    }

    // Load component
    $table = $components->load('table', $alias, $package, '', $data); 

    // Execute get_attributes method, if exists
    if (method_exists($table, 'get_attributes')) { 
        $table->get_attributes($data);
    }

    // Set variables
    $id = $data['id'] ?? 'tbl_' . str_replace(":", "_", $data['table']);
    $has_search = isset($table->has_search) && $table->has_search == 1 ? 1 : 0;
    $sortable = $table->sortable ?? array();
    $form_field = $table->form_field ?? 'none';
    $form_name = $table->form_name ?? $alias;
    $form_value = $table->form_value ?? 'id';

    // Get AJAX data
    $ajaxdata_vars = $data;
    $ajaxdata_vars['id'] = $id;
    unset($ajaxdata_vars['alias']);
    $ajaxdata = http_build_query($ajaxdata_vars);

    // Get table details
    $details = $utils->get_details($table, $id);
    $delete_button = $table->delete_button ?? '';

    // Get pagination attributes
    if ($details['has_pages'] === true) { 
        $pagination_attr = "has_pagination=\"1\" start=\"$details[start]\" page=\"$details[page]\" start_page=\"$details[start_page]\" end_page=\"$details[end_page]\" total=\"$details[total]\" rows_per_page=\"$details[rows_per_page]\" total_pages=\"$details[total_pages]\"";
    } else { $pagination_attr = ''; }

    // Start data table
    $tpl_code = "<a:data_table id=\"$id\" has_search=\"$has_search\" ajax_data=\"$ajaxdata\" form_name=\"$form_name\" delete_button=\"$delete_button\" $pagination_attr><thead>\n";

    // Add header column for radio / checkbox
    $tpl_code .= "<tr>\n";
    if ($form_field == 'checkbox') { 
        $tpl_code .= "\t<a:th><input type=\"checkbox\" name=\"check_all\" value=\"1\" onclick=\"tbl_check_all(this, '$id');\"></a:th>\n";
        if (!preg_match("/\[\]$/", $form_name)) { $form_name .= '[]'; }
    } elseif ($form_field == 'radio') { 
        $tpl_code .= "\t<a:th>&nbsp;</a:th>\n";
    }

    // Add header columns
    foreach ($table->columns as $alias => $name) {
        $can_sort = in_array($alias, $sortable) ? 1 : 0;
        $tpl_code .= "\t<a:th can_sort=\"$can_sort\" alias=\"$alias\">$name</a:th>\n";
    }
    $tpl_code .= "</thead><tbody>\n";

    // Go through table rows
    foreach ($details['rows'] as $row) { 
        $tpl_code .= "<tr>\n";

        // Add form field, if needed
        if ($form_field == 'radio' || $form_field == 'checkbox') { 
            $tpl_code .= "\t<td align=\"center\"><input type=\"$form_field\" name=\"" . $form_name . "\" value=\"" . $row[$form_value] . "\"></td>";
        }

        // Go through columns
        foreach ($table->columns as $alias => $name) { 
            $value = $row[$alias] ?? '';
            $tpl_code .= "\t<td>$value</td>\n";
        }
        $tpl_code .= "</tr>";
    }

    // Finish table
    $tpl_code .= "</tr></tfoot></a:data_table>\n\n";

    // Return
    return view::parse_html($tpl_code);

}


}

