<?php
declare(strict_types = 1);

/**
* Used to define various properties about the theme, plus will 
* override any methods within the /lib/html_tags.php class.  If the same 
* method exists within this class, this method will be executed instead of the one 
* within /lib/html_libs.php
*/
class theme_limitless 
{

    // Properties
    public $area = 'members';
    public $access = 'public';
    public $name = 'Limitless';
    public $description = '';

    // Author details
    public $author_name = '';
    public $author_email = '';
    public $author_url = '';

    /**
    * Envato item ID.  if this is defined, users will need to purchase the theme from ThemeForest first, 
    * and enter their license key before downloading the theme to Apex.  The license key 
    * will be verified via Envato's API, to ensure the user purchased the theme.
    * 
    * You must also specify your Envato username, and the full 
    * URL to the theme on the ThemeForest marketplace.  Please also 
    * ensure you already have a designer account with us, as we do need to 
    * store your Envato API key in our system in order to verify purchases.  Contact us to open a free account.
    */
    public $envato_item_id = '';
    public $envato_username = '';
    public $envato_url = '';

}

