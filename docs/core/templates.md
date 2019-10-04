
# Templates

Naturally, you will want to modify and add pages at least within the public web site.  All TPL template files
for the public site are stored within the /views/tpl/public/ directory of Apex.  These are simply HTML pages
that you can modify as desired, except they do contain some special HTML tags.  If desired, you may view full
documentation on all special HTML tags used by the software in the [Template Tags](../templats_tags.md) page
of this manual.

The naming of the TPL files is directly relative to the URI in the web browser.  For example, when visiting
the URL http://domain.com/about, it will display the TPL file at /views/tpl/public/about.tpl.  For another
example, when visting http://domain.com/services/marketing, it will display the TPL file located at
/views/tpl/public/services/marketing.tpl.  You may feel free to add as many TPL files as desired into this
directory, and they will immediately appear within your web browser at the correct URL without any other
modifications required.





