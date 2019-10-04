
# Themes

All themes are stored within the /views/themes/ and /public/themes/ directories, with each theme residing in
its own sub-directory.  The only files that should reside within the /public/ directory are publicly
accessible assets such as CSS, Javascript and images. Below explains the structure of a theme, and you can
find more information on themes at the below links:

1. [Create and Publish Themes](themes_create.md)
2. [Integrate Existing Theme](themes_integrate.md)
3. [Envata / ThemeForest Designers, Sell More Themes!](themes_envato.md)


### Directory Structure

There are a few standard sub-directories contained within the /views/themes/THEME_ALIAS/ directory as
explained in the below table.

Directory | Description 
------------- |------------- 
/layouts | The various page layouts that are supported by the theme (eg. full_width.tpl, 2_col_right_sidebar.tpl, etc.) and can be named anything you wish.  You can then define which layout each page of the web site uses. 
/sections | The various sections the theme uses.  This is always header.tpl and footer.tpl, but can also include any additional sections you would like (eg. search_bar.tpl). Basically, any chunks of HTML code that are used on many different pages. 
/tpl | Optional, and mirrors the /views/tpl/ directory. Any .tpl files placed within this directory will be automatically copied over during theme installation, and will replace the existing .tpl files.  Useful if you want a certain /public/index.tpl page to be installed. 
/tags.tpl | Contains the HTML code blocks for all elements and widgets used throughout the software, such as boxes / panels, tab controls, data tables, form fields, and more.  Allows you to customize each element / widget to work with the specific CSS of this theme.
/theme.php | The PHP class for the theme, contains basic information on the theme, and allows you to override how any of the special HTML tags are generated.


### /sections

In the /sections/ sub-directory you will place all section files, which are basically chunks of HTML that are
included on multiple pages.  This always includes the header.tpl and footer.tpl files, but can also include
any other files you would like such as for example, search_bar.tpl, right_sidebar.tpl, etc.

Within the section files, use the **~theme_uri ~** merge field to link to the /public/themes/ALIAS/ directory
for public assets such as Javascript, CSS and images.  For example:

~~~
<script src="~theme_uri~/js/myscript.js" type="text/javascript"></script>
<link href="~theme_uri~/css/styles.css" rel="stylesheet" type="text/css" />
<img src="~theme_uri~/images/logo.png">
~~~


### /layouts

Inside the /layouts/ directory is where all page layouts will be stored.  There must be a default.tpl file,
and any other layouts can be named anything you wish, such as for example, right_sidebar.tpl, gallery.tpl,
etc.  You can then specify which layout to use for each page of the web site via the CMS->Pages menu of the
administration panel.  Any pages without a layout defined will use the default.tpl layout.

Here's an example of a small layout file:

~~~html
<a:theme section="header.tpl">

<a:page_contents>

<a:theme section="footer.tpl">
~~~

The above layout simply includes the header.tpl and footer.tpl files from the /sections/ directory, and the
page contents is then replaced with the middle tag.  It's simple as that.  Then for example, you may want to
create a layout that splits the page into a sidebar and main contents, then include a sidebar.tpl section file
within the sidebar.


### /tags.tpl File

This is an important file, and contains the HTML blocks of all elements and widgets that are used throughout the software including navigation menu, 
tab controls, data tables, form fields, and so on.  The default tags.tpl file should work with any typical Bootstrap theme, but you may 
wish to modify as needed for theme specific CSS elements.  When Apex is parsing a template, it will use the HTML code within this file to 
generate the various elements and widgets.  The file itself is very well documented, and should be quite straight forward.


### Special HTML Tags

There are a few special HTML tags available you will want to use within your layout files, as explained in the
below table.

Tag | Description 
------------- |------------- 
`<a:callouts>` | Replaced with the callouts (ie. success / error messages) that are displayed on the top of the page.
`<a:page_title>` | The title of the current page being displayed.  Apex will first check the database to see if a page title has been specifically defined for the page, and if not, will check the TPL code if any `<h1> ... </h1>` tags exist and use that, and otherwise will just default to the site name configuration variable. 
`<a:nav_menu>` | The navigation menu of the area being displayed (administration panel, member area, public web site), and uses the HTML tags located within the `/views/themes/ALIAS/components/nav_menu.tpl` file of the theme being used.  Please refer to these *nav_menu.tpl* files to see proper formatting.
`<a:dropdown_alerts>` | Replaced with drop down items for the larets drop down list, generally located in top-right corner of themes.
`<a:dropdown_messages>` | Replaced with the drop down items for the messages dropdown list, generally located in the top-right corner of themes.


### Merge Fields

The below merge fields are available system-wide and help personalize the site to the client's business.  The
values of these fields are defined within the Settings->General menu of the administration panel, and should be self explanatory.

* ~config.core:domain_name~
* ~current_year~
* ~config.core:site_name~
* ~config.core:site_address~
* ~config.core:site_address2~
* ~config.core:site_email~
* ~config.core:site_phone~
* ~config.core:site_tagline~
* ~config.core:site_facebook~
* ~config.core:site_twitter~
* ~config.core:site_linkedin~
* ~config.core:site_instagram~


