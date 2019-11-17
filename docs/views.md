
# Views

Views are individual web pages that are displayed within the web browser, mainly pages within the
administration panel, member's area, and public web site.  Apex has a very straight forward implementation of
views, as described within this section.


### HTTP Routing.

All templates have a .tpl extension, are stored within the /views/tpl/ directory of the software, and named
relative to the URI being displayed.  For example, if viewing the page at http://localhost/admin/casino/bets,
the software will look for and display the template located at /views/tpl/admin/casino/bets.tpl.

The only exception is the public web site, where location of the view is still relative to the URI being
displayed, but located within the *views/tpl/public/* directory.  For example, if viewing the page at
http://localhost/services, the software will display the view located at */views/tpl/public/services.tpl*.  If
no .tpl file exists at the correct location, the 404.tpl view will be displayed in its place.

The .tpl files are simply standard HTML pages, which also support various special HTML tags to help make
development more efficient and streamlined with cross-theme support. The special HTML tags are described later
in this section.


##### PHP Code

There is a corresponding .php file for every .tpl file, located within the /views/php/ directory, and once
again relative to the URI being displayed.  For example, if the .tpl file at *views/tpl/admin/casino/bets.tpl*
is being displayed, any PHP code found within */views/php/admin/casino/bets.php* will be automatically
executed to handle any specific actions for that individual view.


### Create Views

You must create all views via the apex.php script to ensure they are properly assigned to the correct package,
and included with the package when publishing to a repository.  To create a new view, within terminal type:

`./apex create view URI PACKAGE`

You need to specify the URI without .tpl extension, and the package you want the view included in during
publication.  For example, if developing a package called "casino", and you want to create a view at
http://localhost/admin/casino/games, you would use:

`./apex create view admin/casino/games casino`

This would create blank view files at */views/tpl/admin/casino/games.tpl* and
*/views/php/admin/casino/games.php*, which you may modify as desired.  The URI is immediately active, and will
begin displaying the *games.tpl* upon visiting it.


### More Information

Additional information regarding views can be found at the below links.

1. [Special HTML Tags](views_tags.md)
2. [HTML Forms (`<a:form_table>`)](views_forms.md)
3. [PHP Methods](views_php.md)
4. [Execute PHP on Existing View](views_modifier.md)
5. [AJAX / Web Sockets](views_ajax.md)
6. [2FA via E-Mail / SMS](views_2fa.md)
7. [reCaptcha Integration](views_recaptcha.md)
8. [Dashboards](views_dashboards.md)


