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
        'model' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlflxtb2RlbHM7Cgp1c2UgYXBleFxhcHA7CnVzZSBhcGV4XH5wYWNrYWdlflxpbnRlcmZhY2VzXH5hbGlhc35JbnRlcmZhY2U7CgoKLyoqCiAqIENsYXNzIHRoYXQgaGFuZGxlcyB0aGUgfmFsaWFzfiBtb2RlbCwgaW5jbHVkaW5nIGFsbCAKICogZ2V0dGVyIC8gc2V0dGVyIG1ldGhvZHMuCiAqLwpjbGFzcyB+YWxpYXN+IGltcGxlbWVudHMgfmFsaWFzfkludGVyZmFjZQp7CgogICAgLy8gUHJvcGVydGllcwp+cHJvcGVydGllc34KCi8qKgogKCBDb25zdHJ1Y3Rvciwgd2lsbCBwb3B1bGF0ZSBwcm9wZXJ0aWVzIHdpdGggb3B0aW9uYWwgYXJyYXkgcGFyYW1ldGVyLgogKiAKICogQHBhcmFtIGFycmF5ICRyb3cgT3B0aW9uYWwgYXNzb2NpYXRpdmUgYXJheSBvZiBrZXktdmFsdWUgcGFpcnMgb2YgcHJvcGVydGllcy4KICovCnB1YmxpYyBmdW5jdGlvbiBfX2NvbnN0cnVjdChhcnJheSAkcm93ID0gW10pCnsKICAgIGlmIChjb3VudCgkcm93KSA9PSAwKSB7IHJldHVybjsgfQoKICAgIC8vIEdvIHRocm91Z2ggcHJvcGVydGllcwogICAgZm9yZWFjaCAoYXJyYXlfa2V5cyhnZXRfY2xhc3NfdmFycyhfX0NMQVNTX18pKSBhcyAkYWxpYXMpIHsgCiAgICAgICAgaWYgKCFpc3NldCgkcm93WyRhbGlhc10pKSB7IGNvbnRpbnVlOyB9CiAgICAgICAgJG1ldGhvZCA9ICdzZXRfJyAuICRhbGlhczsKICAgICAgICAkdGhpcy0+JG1ldGhvZCgkcm93WyRhbGlhc10pOwogICAgfQoKfQoKLyoqCiAqIEdlbmVyaWMgY2F0Y2gtYWxsIHNldCAvIGdldCBtZXRob2QuCiAqCiAqIFRoaXMgd2lsbCBoYW5kbGUgc2ltcGxlIGdldCAvIHNldCBtZXRob2RzIGZvciBhbGwgcHJvcGVydGllcy4gIEZvciBhbnkgCiAqIHByb3BlcnRpZXMgdGhhdCByZXF1aXJlIGFueSBleHRyYSB2YWxpZGF0aW9uIC8gZnVuY3Rpb25hbGl0eSwgY3JlYXRlIHRoZSAKICogbmVjZXNzYXJ5IGdldF9QUk9QKCkgLyBzZXRfUFJPUCgpIG1ldGhvZHMgd2l0aGluIHRoaXMgY2xhc3MsIGFuZCB0aGV5IHdpbGwgb3ZlcnJpZGUgdGhpcyBjYXRjaCBhbGwgbWV0aG9kLgogKgogKiBAcGFyYW0gc3RyaW5nICRtZXRob2QgVGhlIG1ldGhvZCBiZWluZyBjYWxsZWQsIGdldF9QUk9QKCkgLyBzZXRfUFJPUCgpLgogKiBAcGFyYW0gbWl4ZWQgJHBhcmFtcyBUaGUgcGFyYW1ldGVycyBwYXNzZWQuCiAqLwpwdWJsaWMgZnVuY3Rpb24gX19jYWxsKHN0cmluZyAkbWV0aG9kLCAkcGFyYW1zKQp7CgogICAgLy8gQ2hlY2sgZm9yIGdldCAvIHNldAogICAgaWYgKCFwcmVnX21hdGNoKCIvXihnZXR8c2V0fClfKC4rKSQvIiwgJG1ldGhvZCwgJG1hdGNoKSkgeyAKICAgICAgICB0aHJvdyBuZXcgQXBleEV4Y2VwdGlvbignZXJyb3InLCB0cignTm8gbWV0aG9kIG5hbWVkIHsxfSBleGlzdHMgd2l0aGluIHRoZSBjbGFzcyB7Mn0nLCAkbWV0aG9kLCBfX0NMQVNTX18pKTsKICAgIH0gZWxzZWlmICghaW5fYXJyYXkoJG1hdGNoWzJdLCBhcnJheV9rZXlzKGdldF9jbGFzc192YXJzKF9fQ0xBU1NfXykpKSkgeyAKICAgICAgICB0aHJvdyBuZXcgQXBleEV4Y2VwdGlvbignZXJyb3InLCB0cignVGhlIHByb3BlcnR5IHsxfSBkb2VzIG5vdCBleGlzdCB3aXRoaW4gdGhlIGNsYXNzIHsyfScsICRtYXRjaFsyXSwgX19DTEFTU19fKSk7CiAgICB9CiAgICAkbmFtZSA9ICRtYXRjaFsyXTsKCiAgICAvLyBSZXR1cm4sIGlmIGdldF8qIG1ldGhvZAogICAgaWYgKCRtYXRjaFsxXSA9PSAnZ2V0JykgeyAKICAgICAgICByZXR1cm4gJHRoaXMtPiRuYW1lID8/IG51bGw7CiAgICB9CgogICAgLy8gR2V0IGRhdGEgdHlwZXMsIGlmIG5lZWRlZAogICAgaWYgKCFpc3NldCgkdGhpcy0+X3R5cGVzKSkgeyAKICAgICAgICAkdGhpcy0+X2dldF9kYXRhX3R5cGVzKCk7CiAgICB9CgogICAgLy8gU2V0IHByb3BlcnR5CiAgICBpZiAoaW5fYXJyYXkoJHRoaXMtPl90eXBlc1skbmFtZV0sIFsnaW50JywnZmxvYXQnLCdib29sJywnc3RyaW5nJ10pKSB7IAogICAgICAgICR0aGlzLT4kbmFtZSA9IGZ0eXBlKCRwYXJhbXNbMF0sICR0aGlzLT5fdHlwZXNbJG5hbWVdKTsKICAgIH0gZWxzZSB7IAogICAgICAgICR0aGlzLT4kbmFtZSA9ICR0aGlzLT5fdHlwZXNbJG5hbWVdID09ICdhcnJheScgPyAkcGFyYW1zIDogJHBhcmFtc1swXTsKICAgIH0KCn0KCi8qKgogKiBHZXQgZGF0YSB0eXBlcy4KICoKICogR29lcyB0aHJvdWdoIGFsbCBwcm9wZXJ0aWVzLCBhbmQgY3JlYXRlcyBhbiBpbnRlcm5hbCAnX3R5cGVzJyBhcnJheSAKICogdGhhdCBjb250YWlucyB0aGUgdHlwZXMgb2YgYWxsIHByb3BlcnRpZXMuCiAqLwpwcml2YXRlIGZ1bmN0aW9uIF9nZXRfZGF0YV90eXBlcygpCnsKCiAgICAkdGhpcy0+X3R5cGVzID0gW107CiAgICAkb2JqZWN0ID0gbmV3IFxSZWZsZWN0aW9uQ2xhc3MoX19DTEFTU19fKTsKICAgIGZvcmVhY2ggKCRvYmplY3QtPmdldFByb3BlcnRpZXMoKSBhcyAkcHJvcCkgeyAKICAgICAgICAkdGhpcy0+X3R5cGVzWyRwcm9wLT5nZXROYW1lKCldID0gJHByb3AtPmhhc1R5cGUoKSA/ICRwcm9wLT5nZXRUeXBlKCktPmdldE5hbWUoKSA6ICdzdHJpbmcnOwogICAgfQoKfQoKLyoqCiAqIEdldCBhbGwgcHJvcGVydGllcyBpbiBhcnJheSBmb3JtYXQuCiAqCiAqIEByZXR1cm4gYXJyYXkgQWxsIHByb3BlcnRpZXMgaW4gYW4gYXJyYXkuCiAqLwpwdWJsaWMgZnVuY3Rpb24gdG9BcnJheSgpOmFycmF5CnsKCiAgICAvLyBTZXQgdmFycwogICAgJHZhcnMgPSBbCn50b2FycmF5X3ZhcnN+CiAgICBdOwoKICAgIC8vIFJldHVybgogICAgcmV0dXJuICR2YXJzOwoKfQoKfQoK', 
        'interface' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBhcGV4XH5wYWNrYWdlflxpbnRlcmZhY2VzOwoKCi8qKgogKiBJbnRlcmZhY2UgZm9yIHRoZSB+YWxpYXN+IG1vZGVsLgogKi8KaW50ZXJmYWNlIH5hbGlhc35JbnRlcmZhY2UKewoKCi8qKgogKiBTZXR0ZXIgbWV0aG9kcwogKi8KfnNldHRlcl9tZXRob2RzfgoKLyoqCiAqIEdldHRlciBtZXRob2RzCiAqLwp+Z2V0dGVyX21ldGhvZHN+CgovKioKICogQWRkaXRpb25hbCBtZXRob2RzCiAqLwpwdWJsaWMgZnVuY3Rpb24gdG9BcnJheSgpOmFycmF5OwoKfQoK'
    ];


/**
 * Create model code
 *
 * Takes in the model.yml file, and generates all necessary code libraries including 
 * interface for the new model.
 *
 * @param string $package The alias of the package to create model under.
 * @param string $alias The desired model alias.
 * @param string $dbtable The database table name to create properties from.
 *
 * @return array Threee elements, the alias, package, and an array of all files generated.
 */
public function create(string $package, string $alias, string $dbtable)
{

    // Set variables
    $this->package = $package;
    $this->dbtable = $dbtable;
    $this->alias = $alias;

    // Perform checks
    $this->perform_checks();

    // Get columns
    $this->columns = db::show_columns($this->dbtable, true);

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

        // Add to properties
        $properties .= "    private $type \$" . $colname . ";\n";
    $toarray_vars .= "        '$colname' => \$this->$colname, \n";

        // Add interface methods
        $interface_setter .= "public function set_" . $colname . "(\$" . $colname . ");\n";
        $interface_getter .= "public function get_" . $colname . "():$type;\n";
    }
    $toarray_vars = preg_replace("/, \n$/", "", $toarray_vars);

    // Create components
    pkg_component::create('lib', $this->package . ':interfaces/' . $this->alias . 'Interface', $this->package);
    pkg_component::create('lib', $this->package . ':models/' . $this->alias, $this->package);

    // Save model code file
    $model = base64_decode($this->code_templates['model']);
    $model = str_replace("~package~", $this->package, $model);
    $model = str_replace("~alias~", $this->alias, $model);
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


