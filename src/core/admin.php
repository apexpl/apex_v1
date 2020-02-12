<?php
declare(strict_types = 1);

namespace apex\core;

use apex\app;
use apex\libc\db;
use apex\libc\debug;
use apex\libc\view;
use apex\libc\forms;
use apex\libc\encrypt;
use apex\app\exceptions\MiscException;


/**
 * Handles all functions relating to administrator accounts, including create, 
 * delete, load, update security questions, etc. 
 */
class admin
{




    // Properties
    private $admin_id = 0;

/**
 * Initiates the class, and accepts an optional ID# of administrator. 
 *
 * @param int $id Optional ID# of administrator to manage / update / delete.
 */
public function __construct(int $id = 0)
{ 
    $this->admin_id = $id;
}

/**
 * Creates a new administrator using the values POSTed. 
 *
 * @return int The ID# of the newly created administrator.
 */
public function create()
{ 

    // Debug
    debug::add(3, tr("Starting to create new administrator and validate form fields"), 'info');

    // Validate form
    forms::validate_form('webapp:admin');

    // Check validation errors
    if (view::has_errors() === true) { return false; }

    // Insert to DB
    db::insert('admin', array(
        'require_2fa' => app::_post('require_2fa'),
        'require_2fa_phone' => app::_post('require_2fa_phone'),
        'username' => strtolower(app::_post('username')),
        'password' => encrypt::hash_string(app::_post('password')), 
        'full_name' => app::_post('full_name'),
        'email' => app::_post('email'),
        'phone_country' => app::_post('phone_country'),
        'phone' => app::_post('phone'))
    );
    $admin_id = db::insert_id();

    // Generate RSA keypair
    encrypt::generate_rsa_keypair((int) $admin_id, 'admin', app::_post('password'));

    /// Debug
    debug::add(1, tr("Successfully created new administrator account, {1}", app::_post('username')), 'info');

    // Return
    return $admin_id;

}

/**
 * Loads the administrator profile 
 *
 * @return array An array containing the administrator's profile
 */
public function load()
{ 

    // Get row
    if (!$row = db::get_idrow('admin', $this->admin_id)) { 
        throw new MiscException('no_admin', $this->admin_id);
    }

    // Debug
    debug::add(3, tr("Loaded the administrator, ID# {1}", $this->admin_id));

    // Return
    return $row;

}

/**
 * Updates the administrator profile using POST values 
 */
public function update()
{ 

    // Demo check
    if (check_package('demo') && $this->admin_id == 1) { 
        view::add_callout("Unable to modify this account, as it is required for the online demo", 'error');
        return false;
    }

    // Debug
    debug::add(3, tr("Starting to update the administrator profile, ID# {1}", $this->admin_id));

    // Set updates array
    $updates = array();
    foreach (array('status','require_2fa','require_2fa_phone', 'full_name','email', 'phone_country', 'phone', 'language', 'timezone') as $var) { 
        if (app::has_post($var)) { $updates[$var] = app::_post($var); }
    }

    // Check password
    if (app::_post('password') != '' && app::_post('password') == app::_post('confirm-password')) { 
        $updates['password'] = encrypt::hash_string(app::_post('password'));
    }

    // Update database
    db::update('admin', $updates, "id = %i", $this->admin_id);

    // Debug
    debug::add(2, tr("Successfully updated administrator profile, ID# {1}", $this->admin_id));

    // Return
    return true;

}

/**
 * Update administrator status 
 *
 * @param string $status The new status of the administrator
 */
public function update_status(string $status)
{ 

    // Demo check
    if (check_package('demo') && $this->admin_id == 1) { 
        view::add_callout("Unable to modify this account, as it is required for the online demo", 'error');
        return false;
    }

    // Update database
    db::update('admin', array('status' => $status), "id = %i", $this->admin_id);

    // Debug
    debug::add(1, tr("Updated administrator status, ID: {1}, status: {2}", $this->admin_id, $status));

}

/**
 * Deletes the administrator from the database 
 */
public function delete()
{ 

    // Demo check
    if (check_package('demo') && $this->admin_id == 1) { 
        view::add_callout("Unable to modify this account, as it is required for the online demo", 'error');
        return false;
    }

    // Delete admin from DB
    db::query("DELETE FROM admin WHERE id = %i", $this->admin_id);

    // Debug
    debug::add(1, tr("Deleted administrator from database, ID: {1}", $this->admin_id), 'info');

}

/**
 * Creates select options for all administrators in the database 
 *
 * @param int $selected The ID# of the administrator that should be selected.  Defaults to 0.
 * @param bool $add_prefix Whether or not to previs label of each option with "Administrator: "
 *
 * @return string The HTML options that can be included in a <select> list.
 */
public function create_select_options(int $selected = 0, bool $add_prefix = false):string
{ 

    // Debug
    debug::add(5, tr("Creating administrator select options"));

    // Create admin options
    $options = '';
    $rows = db::query("SELECT id,username,full_name FROM admin ORDER BY full_name");
    foreach ($rows as $row) { 
        $chk = $row['id'] == $selected ? 'selected="selected"' : '';
        $id = $add_prefix === true ? 'admin:' . $row['id'] : $row['id'];

        $name = $add_prefix === true ? 'Administrator: ' : '';
        $name .= $row['full_name'] . '(' . $row['username'] . ')';
        $options .= "<option value=\"$id\" $chk>$name</option>";
    }

    // Return
    return $options;

}


}

