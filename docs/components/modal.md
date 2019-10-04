
# Modal Component

&nbsp; | &nbsp;
------------- |-------------
**Description:** | A modal / popup that when opened, opaques the browser screen, and displays the contents of the modal in the center of the browser.
**Create Command:** | `php apex.php create modal PACKAGE:ALIAS`
**File Location:** | /src/PACKAGE/modal/ALIAS.php<br />/views/modal/PACKAGE/ALIAS.tpl
**Namespace:** | `apex\PACKAGE\modal\ALIAS`
**HTML Call:** | `<a href="javascript:open_modal(PACKAGE:ALIAS', 'somevar=124&anothervar=abc');">Open Modal</a>`


## TPL File

A .tpl file is created for each modal at /views/modal/PACKAGE/ALIAS.tpl, which can contain any TPL / HTML 
code you would like.  It is passed to the PHP codee for peocessing, before it is displayed in the web browser as the contents of the modal.


## Methods

Below explains all methods available within this class.


### `show(string $html, array $data)`

**Description:** Executed when a modal is first opened, and is used to retrieve any necessary information from 
the database, and assign any needed template variables, mainly.

**Parameters**

Variable | Type | Description
------------- |------------- |-------------
`$html` | string | The contents of the file located at /views/modal/PACKAGE/ALIAS.tpl, if it exists.
`$data` | array | Any additional data / arguments that were passed within the second parameter of the Javascript call that opened the modal.


### `process(array $data)`

**Description:** Will be used to process any form submission from within the modal.  Not yet implemented, but will be soon.




