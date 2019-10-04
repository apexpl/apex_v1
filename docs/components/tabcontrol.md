
# Tab Control Component

&nbsp; | &nbsp;
------------- |-------------
**Description:** | Dynamic and expandable tab controls.  Static tab controls within the TPL code are available, but this component should be used if you believe other packages may want to add additional tab pages to it.  For example, the tab control when managing a user account is dynamic, allowing other packages to easily add additional tab pages to it as needed.
**Create Command:** | `php apex.php create tabcontrol PACKAGE:ALIAS`
**File Location:** | /src/PACKAGE/tabcontrol/ALIAS.php<br />/views/tabpage/PACKAGE/ALIAS/ (directory)
**Namespace:** | `apex\PACKAGE\tabcontrol\ALIAS`
**HTML Tag:** | `<e:function alias="display_tabcontrol" tabcontrol="PACKAGE:ALIAS">`


## Properties

The only property in this class is the `$tabpages` array, which lists all tab pages contained within the tab control in the order they will be displayed.  For example:

~~~php
$tabpages = array(
    'general' => 'General Settings', 
    'security' => 'Security', 
    'reset' => 'Reset Database'
);
~~~

The keys of the array are the alias / filename of the tab pages, and the values are the name of the tab pages as displayed within the web beowser.


## Methods

Below explains all methods available within the tab control.

### `process($data)`

**Description:** Is executed every time the tab control is displayed, and performs any necessary functions from previously submitted forms, 
plus retrieves necessary information from the database and assigns template variables as needed.

The `$data` array is passed to this method, which is simply all attributes contained within the `<e:function>` tag that called the tab control.

//src/PACKAGE/tabcontrol/ALIAS/ (directory)<br />
