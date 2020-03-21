
# Components and Package Development

Although full flexibility is available, Apex does come with a standardized set of components to aide in efficient and streamlined development, plus 
makes things easier to read when other developers work on packages.  All components must be created via the `apex` CLI commands, is quite 
straight forward, and below are some examples.

~~~

// Create library and interface files
./apex create lib mypackage:testlib
./apex create lib mypackage:interfaces/ComeClassInterface

// Views
./apex create view admin/settings/myapi mypackage
./apex create view public/our_services mypackage

// Service provider / adapter
./apex create service mypackage:animals
./apex create adapter mypackage:animals:dog mypackage
./apex create adapter mypackage:animals:tiger mypackage

// Crontab job
./apex create cron mypackage:maintenance
~~~


## CRUD Scaffolding

Apex offers functionality for automated code generation of CRUD scaffolding via a YAML configuration file.  This will 
automatically generate the necessary data table, form, and views necessary for all CRUD functionality.  For full information, please click on 
the below link.

    [ [CRUD Scaffolding](crud.md)  ]


## Remote Access

Apex contains built-in support for remote access, allowing designers / developers to easily update files on a remote server from an Apex installation on their 
local server.  This helps save time, and doesn't require server access.  To full information on how to setup and use remote access, click on the below link.

    [Remote Access Client(remote_access.md)

<a name="components_available"></a>
## Components Available

All supported components are listed in the below table, with links
to full details on each.

Name | Description 
------------- |------------- 
[Library](components/lib.md) | Blank PHP library file for a new class, and is most commonly used within Apex. 
[Views](components/view.md) | One of the core components, and are the individual pages that display output to the browser, and perform necessary actions.
[Workers](components/worker.md) | The workers / listeners that handle all the heavy loading of the software. These listen to messages from RabbitMQ, and are what allow for horizontal scaling. 
[AJAX Function](components/ajax.md) | Easily execute code via AJAX with no Javascript required.  Full library available allowing for easily manipulation of the DOM elements. 
[Auto-Suggest / Complete](components/autosuggest.md) | Standard auto-suggest/complete boxes that allow users to enter a few characters, and a list of possible options is displayed.  Useful for things such as searching user accounts, and can be easily placed in any template within a couple minutes with no Javascript.
[service / adapter](components/service.md) | Service providers and their adapters allowing base functionality to be handled, but slightly differently depending on the respective adapter.  For example, all base transactions are the same, but may be handled slightly differently depending on the type of transaction (iadapter) being processed.
[Crontab Job](components/cron.md) | Easily add in crontab jobs that execute at specified time intervals.  No need to add the crontab job to the server itself, as the in-house cron daemon will execute it when needed. 
[Data Tables](components/table.md) | Quality, stylish data tables with full AJAX functionality including pagination, search, sort, and row deletion.  Flexible, customizable, and can be developed to display any data and placed in templates within a couple short minutes. 
[HTML Form](components/form.md) | Quality HTML forms with full Javascript validation, easily customizable with conditional fields, can be placed in any template with one HTML tag blank, with values from the database or pre-filled with POST variables (ie. in case of user submission errors). 
[HTML Function](components/htmlfunc.md) | Allows you to place one HTML tag in any template, and have it replaced with anything you wish.  Useful when you want the same element / functionality, or variation thereof, placed within multiple locations throughout the system.
[Modal](components/modal.md) | Standard modal / popup dialog allowing for a more user-friendly experience. Can contain any output you want, with built-in functionality for form processing within the modal. 
[Tab Control](components/tabcontrol.md) | Supports both, static and dynamic tab controls. These dynamic tab controls are easily expandable by other packages, plus allow for the easy placement in multiple templates system-wide while providing the same functionality. 
[Tab Page](components/tabpage.md) | Singular pages within an existing tab control, allowing your package to expand on existing tab controls (eg. add a tab when managing a user's profile). 
[Unit Test](components/test.md) | Unit tests via phpUnit, allowing you to provide 100% code coverage with unit tests. 
[CLI Command](components/cli.md) | CLI command that you perform from via the terminal / console.



