
# Create and Publish Themes

All functionality to create, publish, install and remove themes is performed via the apex.php script (or "apex" pchar archive), located
within the installation directory.  The commands available are explained below.


### `Create_theme THEME_ALIAS`

This will createa  new theme on the system with the specified alias.  For example, if you wanted to create the
theme "mycooltheme", within terminal change to the installation directory, and type:

`php apex.php create_theme mycooltheme`

This will create new directories at /views/themes/mycooltheme, and /public/themes/mycooltheme, where you can start
placing all relevant theme files.  There will also be a file at /views/themes/mycooltheme/theme.php with several
variables that you need to modify, as explained in the below table.

Variable | Description 
------------- |------------- 
`$area` | The type of theme, must be either "public" or "members" defining whether the theme is for the public web site or specifically for the member's area.  If it's used for both (ie. user gets redirected to home page upon login, and there is no member's area), then use "public". 
`$access` | Can be either "public" or "private", and only define "private" if it's a custom theme for a single or handful of clients. 
`$name, $description` | The full name and description of the theme.
`$author_name, $author_email, $author_url` | Your name, e-mail address and URL to your website / protfolio.
`$envato_item_id, $envato_username, $envato_url` | Define these if you're a designer who sells on ThemeForest, and this theme is listed on ThemeForest. The URL is the full URL to your theme on ThemeForest.  Be defining these variables, anyone wishing to download your theme will first need to purchase it via ThemeForest, and specify the purchase code upon downloading into Apex, which is verified via Enato's API.


### `change_theme AREA THEME_ALIAS`

Changes the currently actie theme, and you will probably want to use this after creating a theme.  The `AREA`
can be either "public" or "members", and then the alias of the theme to switch to.  For example, to switch to
our newly created "mycooltheme" theme we would use:

`php apex.php change_theme public mycooltheme`

Done.  Now when you view your site at http://localhost/, it will be displayed with the "mycooltheme" theme.


### `publish_theme THEME_ALIAS`

Will package the theme, and publish it to the main Apex repository, making it instantly available to the
entire Apex community (unless access is set to "private"), and will be listed within the Maintenance->Theme
Manager menu of all administration panels.  If you defined the Envata variables within the theme.php file,
users will be required to first purchase the theme, and define their purchase code before being allowed to
download it into Apex.

For example, with our "mycooltheme" theme, within terminal we would type:

`php apex.php publish_theme mycooltheme`


### `install_theme THEME_ALIAS [PURCHASE_CODE]`

Will download and install a theme from the appropriate repository.  If an Envato listed theme, you must also
define the purchase code which can be found by downloading your license key file from ThemeForest in plain
text.  For example:

`php apex.php install_theme mycooltheme`


### `delete_theme THEME_ALIAS`

Will delete the specified theme from the system.




