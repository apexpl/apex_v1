
# Themes

Your technical team will most likely handle this aspect for you, but in the change they don't, you will most
likely want to change the theme of at least the public web site.  You may do this through the
Maintenance->Theme Manager menu of the administration panel.  This will contact all repositories configued on
your system, and retrieve a list of all available themes, some of which are free and some require payment.
Simply find the theme you would like, then within SSH at the terminal prompt within the installation
directory, type:

`./apex install_theme THEME_ALIAS`

Naturally, replace `THEME_ALIAS` with the alias of the theme you want to install, which you will find via the
Maintenace->Theme Manager menu.  If it's a free theme, it will be automatically downloaded from the
repository, installed and activated on your system.  If it is a paid theme, it will prompt you for the
TemeForest license key.  To download this theme, you must first purchase it from ThemeForest, who will then
provide you a license key for your purchase.  Enter the license key when prompted, it will be verified via the
ThemeForest API that it is a valid purchase, then downloaded and installed on your system.




### Modify Themes

There's a good chance you or your web designer will need to modify the theme.  Every theme contains two
directories:

* /themes/THEME_ALIAS -- This directory contains all TPL files for the theme, mainly the header and footer.  You will most likely only need to modify the files located within the /sections/ sub-directory, such as header.tpl and footer.tpl.
* /public/thmes/THEME_ALIAS -- This directory contains all public assets for the theme, such as images, Javascript and CSS.  Modify as desired.


### Integrate a Theme

There's a good chance the theme you wish to use isn't currently integrated with Apex. Don't worry, as it's
extremely easy to integrate virtually any theeeme available on the internet into Apex.  For full information
on how to integrate an existing theme into Apex, please refer to the [Integate an Existing
Theme](../themes_integrate.md) page of this manual.




















