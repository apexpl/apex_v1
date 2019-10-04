
# HTML Function Component

&nbsp; | &nbsp;
------------- |-------------
**Description:** | Basically a widget.  Allows you to place a simple `<e:function>` tag within any template, and have it replaced with any TPL / HTML code desired, plus also execute any desired PHP code.  For example, you may want to develop a search bar that is placed in various templates and locations throughout the system.
**Create Command:** | `php apex.php create htmlfunc PACKAGE:ALIAS`
**File Location:** | /src/PACKAGE/htmlfunc/ALIAS.php<br />/views/htmlfunc/PACKAGE/ALIAS.tpl
**Namespace:** | `apex\PACKAGE\htmlfunc\ALIAS`
**HTML Tag:** | `<e:function alias="PACKAGE:ALIAS">`


## TPL File

There is an optional .tpl file created with very HTML function, located at /views/htmlfunc/PACKAGE/ALIAS.tpl.  You can place any 
TPL / HTML code you would like here, and it will be passed to the PHP code for processing (see below).


## Methods

Below explains all methods within this class.


### `string process(string $html, array $data)`

**Description:** Contains any PHP code that needs to be executed every time the HTML tag is 
included in a template.  Returns the HTML / TPL code that is outputted to the browser in place of the `<e:function>` tag.


**Parameters**

Variable | Type | Description
-------------  |------------- |-------------
`$html` | string | The contens of the .tpl file at /views/htmlfunc/PACKAGE/ALIAS.tpl, if the file exists.
`$data` | array | All attributes within the `<e:function>` HTML tag that called the function.


## Example

For example, you may create a HTML functions with:

`php apex.php create htmlfunc casino:wallet_summary`

This will create a PHP file at /etc/casino/htmlfunc/wallet_summary.php.  It can then be executed within any template within Apex by placing the HTML tag:

`<e:function alias="casino:wallet_summary" userid="~userid~" somevar="1234">`

You can add any attributes you would like to the HTML tag, which is then passed to the `process()` method within the `$data` array.  The HTML tag will then be replaced 
with whatever the `process()` method returns.





