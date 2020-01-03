
# Integrate Existing Theme

You can easily integrate any HTML / CSS theme available on the internet from places such as ThemeForest.  It
should generally only take about 30 minutes to integrate an existing theme.  To do so, follow the below steps.

1. In terminal, move to the Apex installation directory, and type `./apex create theme theme_alias`
2.  Upload all public assets (Javascript, CSS, images) to the /public/themes/theme_alias/ directory.
3. Slice the main page into header.tpl and footer.tpl files, and place them within the */views/themes/theme_alias/sections/* directory.  Save the remaining homepage body contents to */views/themes/theme_alias/tpl/index.tpl*.
4. Within terminal run: `./apex init_theme theme_alias`, which will go through the files created in previous step, and add the ~theme_uri~ merge field to all internal links as necessary.
5. Add the `a:nav_menu>` HTML tag where needed in place of the navigation menu.  If you only want the navigation menu to be displayed to logged in users, use: `<a:if ~userid~ != 0> <a:nav_menu> </a:if>`
6. Add `<a:page_title textonly="1">` where needed, of course within the `<title>` tags of the header, then usually once at the bottom of the header.tpl file.
7. Add the `<a:callouts>` tag where necessary, generally at the very bottom of the header.tpl file.  This will be replaced with any callouts / messages (ie. user submission errors).
8. Add the `<a:scoail_links>` tag where necessary, which will be replaced with all social media icons / links as defined by the administrator.
9. Create a file at /views/themes/theme_alias/layouts/default.tpl as necessary.  Below shows some example code of what the file may look like.
10. Modify the */views/themes/theme_alias/tags.tpl* file as necessary, and update the various HTML snippets as needed for this specific theme.
11. Add a screenshot.png file to the /public/themes/THEME_ALIAS? directory, with the size of 300x225px.
12.  Modify the file at /themes/THEME_ALIAS/theme.php, and define the few variables at the top as necessary.

### Example Layouts

~~~html
<a:theme section="header.tpl">

<a:page_contents>

<a:theme section="footer.tpl">
~~~

That's it.  To activate the theme, open terminal, change to the installation directory and type:

`./apex change_theme public THEME_ALIAS`

The theme will now be active on the public web site.



