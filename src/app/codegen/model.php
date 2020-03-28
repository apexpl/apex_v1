<?php
declare(strict_types = 1);

namespace apex\app\codegen;

use apexapp;
use apex\libc\{db, debug, components};
use apex\app\pkg\pkg_component;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Inflector\Inflector;
use apex\app\exceptions\ApexException;


/**
 * Handles code generation for models through the model.yml file, 
 * and the 'gen_model' CLI command.  Please refer to the developer 
 * documentation for full details.
 */
class model
{

    // Properties
    private string $package;
    private string $dbtable;
    private string $alias;
    private array $exclude = [];
    private array $columns = [];

    // Code templates
    private array $code_templates = [
        'model' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlflxtb2RlbHM7Cgp1c2UgYXBleFxhcHA7CnVzZSBhcGV4XH5wYWNrYWdlflxpbnRlcmZhY2VzXH5hbGlhc35JbnRlcmZhY2U7CgoKLyoqCiAqIENsYXNzIHRoYXQgaGFuZGxlcyB0aGUgfmFsaWFzfiBtb2RlbCwgaW5jbHVkaW5nIGFsbCAKICogZ2V0dGVyIC8gc2V0dGVyIG1ldGhvZHMuCiAqLwpjbGFzcyB+YWxpYXN+IGltcGxlbWVudHMgfmFsaWFzfkludGVyZmFjZQp7CgogICAgLy8gUHJvcGVydGllcwp+cHJvcGVydGllc34KCn5zZXR0ZXJfbWV0aG9kc34KCgp+Z2V0dGVyX21ldGhvZHN+CgovKioKICogR2V0IGFsbCBwcm9wZXJ0aWVzIGluIGFycmF5IGZvcm1hdC4KICoKICogQHJldHVybiBhcnJheSBBbGwgcHJvcGVydGllcyBpbiBhbiBhcnJheS4KICovCnB1YmxpYyBmdW5jdGlvbiB0b0FycmF5KCk6YXJyYXkKewoKICAgIC8vIFNldCB2YXJzCiAgICAkdmFycyA9IFsKfnRvYXJyYXlfdmFyc34KICAgIF07CgogICAgLy8gUmV0dXJuCiAgICByZXR1cm4gJHZhcnM7Cgp9Cgp9Cgo=', 
        'interface' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlflxpbnRlcmZhY2VzOwoKdXNlIGFwZXhcYXBwOwoKCi8qKgogKiBJbnRlcmZhY2UgZm9yIHRoZSB+YWxpYXN+IG1vZGVsLgogKi8KaW50ZXJmYWNlIH5hbGlhc35JbnRlcmZhY2UKewoKfnNldHRlcl9tZXRob2RzfgoKfmdldHRlcl9tZXRob2RzfgoKCn0KCg==', 
        'setter' => 'Ci8qKgogKiBTZXQgdGhlIH5jb2xuYW1lfgogKgogKiBAcGFyYW0gfnR5cGV+ICR+Y29sbmFtZX4gVmFsdWUgb2YgdGhlIH5jb2xuYW1lfgogKi8KcHVibGljIGZ1bmN0aW9uIHNldF9+Y29sbmFtZX4ofnR5cGV+ICR+Y29sbmFtZX4pIHsgJHRoaXMtPn5jb2xuYW1lfiA9ICR+Y29sbmFtZX47IH0KCg==', 
        'getter' => 'Ci8qKgogKiBHZXQgdmFsdWUgb2YgfmNvbG5hbWV+CiAqCiAqIEByZXR1cm4gfnR5cGV+IFZhbHVlIG9mIH5jb2xuYW1lfgogKi8KcHVibGljIGZ1bmN0aW9uIGdldF9+Y29sbmFtZX4oKTp+dHlwZX4geyByZXR1cm4gJHRoaXMtPn5jb2xuYW1lfjsgfQoKCg==', 
        'interface_setter' => 'LyoqCiAqIFNldCB0aGUgfmNvbG5hbWV+CiAqCiAqIEBwYXJhbSB+dHlwZX4gJH5jb2xuYW1lfiBWYWx1ZSBvZiB0aGUgfmNvbG5hbWV+CiAqLwpwdWJsaWMgZnVuY3Rpb24gc2V0X35jb2xuYW1lfih+dHlwZX4gJH5jb2xuYW1lfik7Cgo=', 
        'interface_getter' => 'Ci8qKgogKiBHZXQgdGhlIH5jb2xuYW1lfgogKgogKiBAcmV0dXJuIH50eXBlfiBUaGUgdmFsdWUgb2YgdGhlIH5jb2xuYW1lfgogKi8KcHVibGljIGZ1bmN0aW9uIGdldF9+Y29sbmFtZX4oKTp+dHlwZX47Cgo='
    ];


/**
 * Create model code
 *
 * Takes in the model.yml file, and generates all necessary code libraries including 
 * interface for the new model.
 *
 * @param string $file The full path to the model.yml file to use.
 *
 * @return array Threee elements, the alias, package, and an array of all files generated.
 */
public function create(string $file = '')
{

    // Load YAML file
    try {
        $vars = Yaml::parseFile($file);
    } catch (ParseException $e) { 
        throw new ApexException('error', tr("Unable to parse YAML file for CRUD creation.  Message: {1}", $e->getMessage()));
    }

    // Set variables
    $this->package = $vars['package'] ?? '';
    $this->dbtable = $vars['dbtable'] ?? '';
    $this->alias = $vars['alias'] ?? preg_replace("/^" . $this->package . "_/", "", $this->dbtable);
    $this->exclude = $vars['exclude'] ?? [];

    // Perform checks
    $this->perform_checks();

    // Get columns
    $this->columns = db::show_columns($this->dbtable, true);
    if (isset($this->columns['id'])) { unset($this->columns['id']); }

    // Create libraries
    $files = $this->create_libraries();

    // Return
    return array($this->alias, $this->package, $files);

}

/**
 * Perform checks
 */
private function perform_checks()
{

    // Ensure database table exists
    $tables = db::show_tables();
    if (!in_array($this->dbtable, $tables)) { 
        throw new ApexException('error', tr("The database table does not exist, {1}", $this->dbtable));
    }

    // Ensure package exists
    if (!$row = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", $this->package)) {
        throw new PackageException('not_exists', $this->package);
    }

    // Check alias
    if ($this->alias == '' || preg_match("/[\W\s]/", $this->alias)) { 
        throw new ApexException('error', tr("Invalid alias defined, {1}", $this->alias));
    }

    // Check if components exist
    if (components::check('lib', $this->package . ':interfaces/' . $this->alias . 'Interface')) { 
        throw new ComponentException('already_exists', 'lib', $this->package . ':' . $this->alias . 'Interface');
    } elseif (components::check('lib', $this->package . ':models/' . $this->alias)) { 
        throw new ComponentException('already_exists', 'lib', $this->package . ':models/' . $this->alias_plural);
    }

}

/**
 * Create libraries
 *
 * @return array List of files that were created
 */
private function create_libraries():array
{

    // Initialize
    $properties = '';
    $toarray_vars = '';
    $setter = '';
    $getter = '';
    $interface_setter = '';
    $interface_getter = '';

    // Go through columns
    foreach ($this->columns as $colname => $coltype) { 

        // Exclude, if needed
        if (in_array($colname, $this->exclude)) { 
            continue;
        }

        // Get variable type
        if (preg_match("/^decimal/i", $coltype)) { $type = 'float'; }
        elseif (preg_match("/^tinyint/i", $coltype)) { $type = 'bool'; }
        elseif (preg_match("/int/i", $coltype)) { $type = 'int'; }
        else { $type = 'string'; }

        // Add to properties, and get setter / getter methods
        $properties .= "    private $type \$" . $colname . ";\n";
    $toarray_vars .= "        '$colname' => \$this->$colname, \n";
        $snippets = [
            'setter' => base64_decode($this->code_templates['setter']), 
            'getter' => base64_decode($this->code_templates['getter']), 
            'interface_setter' => base64_decode($this->code_templates['interface_setter']), 
            'interface_getter' => base64_decode($this->code_templates['interface_getter'])
        ];

        // Replace variables
        foreach ($snippets as $key => $code) { 
            $snippets[$key] = str_replace("~alias~", $this->alias, $snippets[$key]);
            $snippets[$key] = str_replace("~package~", $this->package, $snippets[$key]);
            $snippets[$key] = str_replace("~type~", $type, $snippets[$key]);
            $snippets[$key] = str_replace("~colname~", $colname, $snippets[$key]);
        }

        // Add setter / getter methods
        $setter .= $snippets['setter'];
        $getter .= $snippets['getter'];
        $interface_setter .= $snippets['interface_setter'];
        $interface_getter .= $snippets['interface_getter'];
    }
    $toarray_vars = preg_replace("/, \n$/", "", $toarray_vars);

    // Create components
    pkg_component::create('lib', $this->package . ':interfaces/' . $this->alias . 'Interface', $this->package);
    pkg_component::create('lib', $this->package . ':models/' . $this->alias, $this->package);

    // Save model code file
    $model = base64_decode($this->code_templates['model']);
    $model = str_replace("~package~", $this->package, $model);
    $model = str_replace("~alias~", $this->alias, $model);
    $model = str_replace("~setter_methods~", $setter, $model);
    $model = str_replace("~getter_methods~", $getter, $model);
    $model = str_replace("~properties~", $properties, $model);
    $model = str_replace("~toarray_vars~", $toarray_vars, $model);
    file_put_contents(SITE_PATH . '/src/' . $this->package . '/models/' . $this->alias . '.php', $model);

    // Save interface code file
    $interface = base64_decode($this->code_templates['interface']);
    $interface = str_replace("~package~", $this->package, $interface);
    $interface = str_replace("~alias~", $this->alias, $interface);
    $interface = str_replace("~setter_methods~", $interface_setter, $interface);
    $interface = str_replace("~getter_methods~", $interface_getter, $interface);
    file_put_contents(SITE_PATH . '/src/' . $this->package . '/interfaces/' . $this->alias . 'Interface.php', $interface);

    // Return
    return [
        'src/' . $this->package . '/models/' . $this->alias . '.php', 
        'src/' . $this->package . '/interfaces/' . $this->alias . 'Interface.php'
    ];

}

}


