
# CMS

The administration panel contains a CMS menu, which allows you to easily manage navigation menus, page titles
/ layouts, and placeholders.  Although these menus should be pretty straight forward, this page gives a brief
description of each.


### Menus

Allows you to manage the navigation menus within both, the public web site and the member's area.  Both tables
contain an Active column, allowing you to check / uncheck which menus you want the system to display.  There
is also a tab allowing you to create additional menus, which more than likely will only be used for the public
web site.  Any changes made or new menus added will immediately appear on the site, allowing you to easily
manage the navigation menus without having to modify any HTML code.


### Titles / Layouts

If desired, through this menu you may change the title and/or layout of any specific page within the public
web site.  This menu simply contains a table listing all pages within the public site, and you may make any
desired changes as you wish.  Each theme comes with different layouts, stored within the
/themes/THEME_ALIAS/layouts/ directory, as you may sometimes want different layouts for different pages.  For
example, some themes will come with layouts such as full width, two column right sidebar, two column left
sidebar, etc.


### Placeholders

All templates within the public web site and member's area contain various placeholders, which can be replaced
with any text / HTML code you desire.  This menu allows you to enter the contents of each placeholder.  It is
highly recommended you define all additional text / HTML code you wish to place within the web pages here,
ecspecially the member's area, instead of manually modifying the TPL template files.  This is because
sometimes an upgrade will contain an updated version of templates within the member's area, so upon installing
the upgrade, it will overwrite the TPL file, wiping out any manual modification you may have made.  However,
all text / HTML code you've defined within the placeholders will remain intact, even if the TPL file is
overwritten with a newer version.







