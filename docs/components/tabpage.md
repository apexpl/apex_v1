
# Tab Page Component

&nbsp; | &nbsp;
------------- |-------------
**Description:** | A single tab page within an existing tab control.  Useful if your package wants to add an extra tab page to for example, the manage user account tab control.
**Create Command:** | `./apex create tabpage PACKAGE:PARENT:ALIAS OWNER`
**File Location:** | /src/PACKAGE/tabcontrol/PARENT/ALIAS.php<br />/views/tabpage/PACKAGE/PARENT/ALIAS.tpl
**Namespace:** | `apex\PACKAGE\tabcontrol\PARENT\ALIAS`


When creating a tab page, you must define the owner package, so it is correctly included when publishing your package.  For example:

`./apex create tabpage users:manage:bets casino`

This would add a new "bets" tab page to the existing "users:manage" tab control, which is displayed when managing a user's account.  This tab page will be 
owned by the "casino" package, and included when publishing the package.


## TPL File

A new .tpl file will be created at /views/tabpage/PACKAGE/PARENT/ALIAS.tpl, and needs to contain the TPL / HTML code defining the 
contents of the tab page.


## Methods

Below explains all methods within this class.


### `process(array $data)`

**Description:** Simply performs any necessary actions from previously submitted forms, and also retrieves necessary information 
from the database, and assigns template variables as needed.




