
# Library Component

&nbsp; | &nbsp;
------------- |-------------
**Description:** | One of the main components in Apex, and simply a blank PHP class file allowing you to develop andything you need / wish.
**Create Command:** | `./apex create lib PACKAGE:ALIAS`
**File Location:** | /src/PACKAGE/ALIAS.php
**Namespace:** | `\apex\PACKAGE\ALIAS`


For example, if you create a library with:

`./apex create lib myblog:post`

A new file will be located at /src/myblog/post.php that looks like:

```php
<?php
declare(strict_types = 1);

namespace apex\myblog;

use apex\DB;
use apex\registry;
use apex\log;
use apex\debug;

class post
{


}
~~~

You can then develop all the desired properties and methods you need.  Within other PHP files you can easily include this 
class with simply:

`use apex\myblog\post;`



