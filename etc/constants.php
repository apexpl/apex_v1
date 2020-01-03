<?php

// Define component types
define('COMPONENT_TYPES', array(
    'adapter' => 'src/~package~/service/~parent~/~alias~.php', 
    'ajax' => 'src/~package~/ajax/~alias~.php',  
    'autosuggest' => 'src/~package~/autosuggest/~alias~.php', 
    'cli' => 'src/~package~/cli/~alias~.php',  
    'controller' => 'src/~package~/controller/~parent~/~alias~.php', 
    'cron' => 'src/~package~/cron/~alias~.php', 
    'form' => 'src/~package~/form/~alias~.php',  
    'htmlfunc' => 'src/~package~/htmlfunc/~alias~.php',  
    'lib' => 'src/~package~/~alias~.php', 
    'modal' => 'src/~package~/modal/~alias~.php',  
    'service' => 'src/~package~/service/~alias~.php',  
    'tabcontrol' => 'src/~package~/tabcontrol/~alias~.php', 
    'tabpage' => 'src/~package~/tabcontrol/~parent~/~alias~.php',   
    'table' => 'src/~package~/table/~alias~.php',  
    'view' => 'views/php/~alias~.php',  
    'test' => 'tests/~package~/~alias~_test.php', 
    'worker' => 'src/~package~/worker/~alias~.php')
);

// Component .tpl files
define('COMPONENT_TPL_FILES', array(
    'view' => 'views/tpl/~alias~.tpl', 
    'htmlfunc' => 'views/components/htmlfunc/~package~/~alias~.tpl',
    'modal' => 'views/components/modal/~package~/~alias~.tpl',  
    'tabpage' => 'views/components/tabpage/~package~/~parent~/~alias~.tpl')
);

// Component parent types
define('COMPONENT_PARENT_TYPES', array(
    'hash_var' => 'hash', 
    'adapter' => 'service', 
    'tabpage' => 'tabcontrol')
);

// Package configuration files
define('PACKAGE_CONFIG_FILES', array(
    'package.php', 
    'install.sql', 
    'install_after.sql', 
    'remove.sql', 
    'reset.sql')
);



