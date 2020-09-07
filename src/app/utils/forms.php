<?php
declare(strict_types = 1);

namespace apex\app\utils;

use apex\app;
use apex\libc\debug;
use apex\libc\view;
use apex\libc\components;
use apex\app\exceptions\ApexException;
use apex\app\exceptions\ComponentException;
use apex\app\exceptions\FormException;


/**
 * HTML Forms Library
 *
 * Service: apex\libc\forms
 *
 * Handles various form functionality such as easy server-side validation of 
 * form components, obtaining an uploaded file, the value of a checkbox, date 
 * interval, etc. 
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
 * use apex\libc\forms;
 *
 * // Validate the users:register form
 * forms::validate_form('users:register');
 *
 */
class forms
{



/**
 * Validate form fields 
 *
 * Validates form fields as needed.  Can either pass any errors to the 
 * template engine, or trigger an error displaying the error template. if 
 * $error_type is 'template', you can check if errors occured via the 
 * template:has_errors property. 
 *
 * @param string $error_type Must be either 'template' or 'error'.
 * @param array $required One dimensional array containg names of form fields that are required, and can not be left blank.
 * @param array $datatypes Associate array specifying which form fields should match which type.
 * @param array $minlength Associate array for any form fields that have a minimum required length.
 * @param array $maxlength Associate array for any form fields that have a maximum required length.
 * @param array $labels Optionaly specify labels for form fields to use within error messages.  Defaults to ucwords() version of field name.
 */
public function validate_fields(string $error_type = 'template', array $required = array(), array $datatypes = array(), array $minlength = array(), array $maxlength = array(), array $labels = array())
{ 

    // Debug
    debug::add(4, tr("Starting to validate various form fields"));

    // Check required fields
    foreach ($required as $var) { 
        $value = app::_post($var) ?? '';
        if ($value != '') { continue; }

        // Give error message
        $label = $labels[$var] ?? ucwords(str_replace("_", " ", $var));
        if ($error_type == 'template') { 
            view::add_callout(tr("The form field %s was left blank, and is required", $label), 'error'); 
        } else { 
            throw new FormException('field_required', $label); 
        }
    }

    // Check data types
    foreach ($datatypes as $var => $type) { 

        // Set variables
        $errmsg = '';
        $value = app::_post($var) ?? '';
        $label = $labels[$var] ?? ucwords(str_replace("_", " ", $var));

        // Check type
        if ($type == 'alphanum' && !preg_match('/^[a-zA-Z]+[a-zA-Z0-9._]+$/', $value)) { 
            $errmsg = "The form field %s must be alpha-numeric, and can not contain spaces or special characters.";

        } elseif ($type == 'integer' && preg_match("/\D/", (string) $value)) { 
            $errmsg = "The form field %s must be an integer only.";

        } elseif ($type == 'decimal' && $value != '' && !preg_match("/^[0-9]+(\.[0-9]{1,8})?$/", (string) $value)) { 
            $errmsg = "The form field %s can only be a decimal / amount.";

        } elseif ($type == 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) { 
            $errmsg = "The form field %s must be a valid e-mail address.";

        } elseif ($type == 'url' && !filter_var($value, FILTER_VALIDATE_URL)) { 
            $errmsg = "The form field %s must be a valid URL.";
        }

        // Give error if needed
        if ($errmsg == '') { continue; }
        if ($error_type == 'template') { 
            view::add_callout(tr($errmsg, $label), 'error'); 
        } else { 
            throw new ApexException('error', $errmsg, $label); 
        }
    }

    // Minlength
    foreach ($minlength as $var => $length) { 
        $value = app::_post($var) ?? '';
        if (strlen($value) >= $length) { continue; }
        $label = $labels[$var] ?? ucwords(str_replace("_", " ", $var));

        // Get error
        $errmsg = tr("The form field %s must be a minimum of %i characters in length.", $label, $length);

        // Set error
        if ($error_type == 'template') { 
            view::add_callout($errmsg, 'error'); 
        } else {
            throw new ApexException('error', $errmsg); 
        }
    }

    // Max lengths
    foreach ($maxlength as $var => $length) { 
        $value = app::_post($var) ?? '';

        // Check
        if (strlen($value) > $length) { 
            $label = $labels[$var] ?? ucwords(str_replace("_", " ", $var));
            $errmsg = tr("The form field %s can not exceed a maximum of %i characters.", $label, $length);

            if ($error_type == 'template') { view::add_callout($errmsg, 'error'); }
            else { throw new ApexException('error', $errmsg); }
        }
    }

    // Debug
    debug::add(4, "Completed validating all various form fields");


}

/**
 * Validates a Apex supported 'form' component, using the $form_fields array 
 * provided by the component. 
 *
 * @param string $form_alias Standard formatted Apex compoent alias of PACKAGE:ALIAS
 * @param string $error_type Must be either 'template' or 'error'.
 * @param array $data Optional array that will be passed to the validate() method of the form component for additional validation.
 */
public function validate_form(string $form_alias, string $error_type = 'template', array $data = array()):bool
{ 

    // Debug
    debug::add(4, tr("Starting to validate form component with alias {1}", $form_alias));

    // Check form alias
    if (!list($package, $parent, $alias) = components::check('form', $form_alias)) { 
        throw new ComponentException('not_exists_alias', 'form', $form_alias);
    }

    // Load component
    $form = components::load('form', $alias, $package);

    // Get fields
    $fields = $form->get_fields($data);

    // Set blank arrays
    $a_required = array();
    $a_datatypes = array();
    $a_minlength = array();
    $a_maxlength = array();
    $a_labels = array();

    // Go through form fields
    foreach ($fields as $name => $vars) { 

        // Set variables
        $required = $vars['required'] ?? 0;
        $type = $vars['datatype'] ?? '';
        $minlength = $vars['minlength'] ?? 0;
        $maxlength = $vars['maxlength'] ?? 0;
        $label = $vars['label'] ?? ucwords(str_replace("_", " ", $name));

        // Add to needed arrays
        if ($required == 1) { $a_required[] = $name; }
        if ($type != '') { $a_datatypes[$name] = $type; }
        if ($minlength > 0) { $a_minlength[$name] = $minlength; }
        if ($maxlength > 0) { $a_maxlength = $maxlength; }
        $a_label = $label;
    }

    // Validate the form
    $this->validate_fields($error_type, $a_required, $a_datatypes, $a_minlength, $a_maxlength, $a_labels);

    // Perform any additional form validation
    if (method_exists($form, 'validate')) { 
        $form->validate($data);
    }

    // Debug
    debug::add(2, tr("Completed validating form component with alias {1}", $form_alias));

    // Return
    $result = view::has_errors() === true ? false : true;
    return $result;

}

/**
 * Get checkbox values 
 *
 * Get values of a checkbox form field.  Does not give undefined errors if no 
 * checkboxes are ticked, and ensures always an array is returned instead of a 
 * scalar. 
 *
 * @param string $var The form field name of the checkbox field.
 */
public function get_chk(string $var):array
{ 

    // Get values
    if (app::has_post($var) && is_array(app::_post($var))) { $values = app::_post($var); }
    elseif (app::has_post($var)) { $values = array(app::_post($var)); }
    else { $values = array(); }

    // Return
    return $values;

}

/**
 * Get date interval 
 *
 * Returns the value of the <e:date_interval> tag, which consits of two form 
 * fields, the period (days, weeks, months, years), and the length. 
 *
 * @param string $name The name of the form field used within the <e:date_interval> tag.
 */
public function get_date_interval(string $name):string
{ 

    // Check
    if (!app::has_post($name . '_period')) { return ''; }
    if (!app::has_post($name . '_num')) { return ''; }
    if (app::_post($name . '_num') == '') { return ''; }
    if (preg_match("/\D/", (string) app::_post($name . '_num'))) { return ''; }
    if (app::_post($name . '_period') == '') { return ''; }

    // Return
    $interval = app::_post($name . '_period') . app::_post($name . '_num');
    return $interval;

}

/**
 * Get date form field.
 *
 * Checks the <a:date> form field whether or not a date was selected, and 
 * if yes, returns date formatted in YYY-MM-DD format.  Otherwise, results empty string.
 *
 * @param string $name The name of the <a:date> form field.
 *
 * @return mixed String formatted in YYY-MM-DD or empty string otherwise.
 */
public function get_date(string $name)
{

    // Initial checks
    if (!app::has_post($name . '_day')) { return '1900-01-01'; }
    if (!app::has_post($name . '_month')) { return '1900-01-01'; }
    if (!app::has_post($name . '_year')) { return '1900-01-01'; }
    if (app::_post($name . '_day') == '') { return '1900-01-01'; }
    if (app::_post($name . '_month') == '') { return '1900-01-01'; }
    if (app::_post($name . '_year') == '') { return '1900-01-01'; }

    // Get date
    $date = implode('-', array(
        app::_post($name . '_year'), 
        str_pad(app::_post($name . '_month'), 2, '0', STR_PAD_LEFT), 
        str_pad(app::_post($name . '_day'), 2, '0', STR_PAD_LEFT), 
    ));

    // Return
    return $date;

}




}

