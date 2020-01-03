
# Request Routing (http / cli)

All incoming requests to Apex are handled either via HTTP or CLI (command line). Regardless of request origin,
the [app class](app.md) remains the main / central class which helps facilitate the request and response.


## Http / Middleware Controllers

How the HTTP requests are routed depends directly on the first segment of the URI, and the http / middleware
adapters installed within the */src/core/service/http_requests/* directory.  If there is a adapter
named the same as the first segment of the UIR, the request will be passed off to it.  Otherwise the request
will be passed to the default "http.php" adapter and treated as a page of the public web site.

For example, if accessing */admin/users/create*, the request will be handled by the "admin.php" adapter. If
accessing */image/product/53/thumb.jpg* the request will be handled by the "image.php" adapter.  All
requests for which the first segment of the URI does not match a specific adapter will be handled by the
default "http.php" adapter and treated as a page of the public web site.


### Views Directory Structure

Many of the requests will output a view to the web browser (administration panel, public web site, etc.), and
the view displayed also depends directly on the URI being accessed. All views are stored within the
*/views/tpl/* directory, and Apex will simply display the .tpl file relative to the URI.  For example, if
accessing the */admin/users/create* URI, the view at */views/tpl/admin/users/create.tpl* will be displayed.

The one exception to this is the public web site, where the view displayed is the URI relative to the
*/views/tpl/public/* directory.  For example, if accessing the */services* URI, the view located at
*/views/tpl/public/services.tpl* will be displayed.  If no .tpl file exists at the correct location, the
correct 404.tpl view will be displayed.

##### Views PHP Code

On top of the .tpl files, all views also have an associated .php file located within the */views/php/*
directory at the same relative location.  Any PHP code placed in these files is automatically executed when
the view is displayed, and is specific to that view. Please recall the [Apex Request
Variables](app.md#apex_request) within the app class, as they will be used often within these PHP files.


### Create Http / Middleware Adapter

At times you will need to create your own HTTP adapter, allowing all incoing requests to be handled in a 
certain way.  For example, you may want all requests to the /invoice/ URI to be routed through a different adapter.  To do this you 
would createa  new adapter, for example if the package you're developing is "mycart" in terminal you would type:

`./apex create adapter core:http_requests:invoice mycard`

This will create a new PHP file at */src/core/service/http_requests/invoice.php*, and will be packaged with the "mycart" package.  Simply 
modify this file as desired, and add a `process()` method, which will be executed for every incoming HTTP request.  More than likely, the 
app::get_uri_segments() method will be useful, as it returns an array of all URI segments separated by a / character.  For example:

~~~php
<?php
declare(strict_types = 1);

namespace apex\app\core\service\http_requests;

use apex\app;
use apex\libc\db;
use apex\libc\debug;
use apex\libc\view;


class invoice
{

    public function process() { 

        // Get invoice ID#
        $invoice_id = app::get_uri_segments()[0] ?? 'Unknown';
        app::set_res_body("Invoice ID: $invoice_id");
    }
}
~~~

With the above example, if you visit the */invoice/85542TH34* URI within your web browser, it 
will output "Invoice ID: 85542TH34".


## CLI Commands

Through the /apexscript), you can develop and execute custom CLI commands by creating 
a "cli" component for each command.  For example, if developing a package named "mycard", and you want a custom CLI command 
named "archive_orders", you could create a cli component within terminal with:

`./apex create cli mycard:archive_orders`

This will create a new PHP file at */src/mycard/cli/archive_orders.php*, and simply add a `process()` method into the PHP class, 
which will be executed every time the CLI command is performed.  You can then run the CLI command any time 
via terminal with:

`./apex mycard.archive_orders`

The above will execute the `process()` method within the */src/mycard/cli/archive_orders.php* PHP class.



