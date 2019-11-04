
# Controller Component

&nbsp; | &nbsp;
------------- |-------------
**Description:** | Used to implement a standardized flow for a process that differs depending on some type / controller.  For example, http_requests are one set of controllers that handle all incoming HTTP requests to Apex depending on the first element of the URI.  Another is the various transaction types available (deposit, withdraw, fee, commission, membership_fee) which all need to follow the same logic, but handle the transactions slightly differently.
**Create Command:** Parent: `php apex.php create controller PACKAGE:ALIAS`<br />Child: `php apex.php create controller PACKAGE:PARENT:ALIAS OWNER`
**File Location:** | Parent: /src/PACKAGE/controller/ALIAS.php<br />Child: /src/PACKAGE/controller/PARENT/ALIAS.php
**Namespace:** | Parent: `\apex\PACKAGE\controller\ALIAS`<br />Child: `\apex\PACKAGE\controller\PARENT\ALIAS`


## Parent Controller

This is simply an abstract class that defines the various properties and methods the child controllers 
need to follow.  A good example of this is the file located at /src/transaction/controller/transaction.php, which defines the various 
methods such as `get_amount()`, `approved()`, `declined()`, and `pending()`.  These methods are required by the child controllers, 
but may handle a declined / approved transaction a little differently (eg. if membership fee, deactivate the user account upon a declined transaction).

For example, say you're doing a gambling project of some kind with different games.  Each game needs to follow the same general process 
flow, but handles things like bets, wins and losses differently.  You could create a parent controller for this with:

`php apex.php create controller casino:games`

This assumes you're developing the package "casino", and would place a new PGP file at /src/casino/controller/games.php where you can 
define the various abstract methods.  For example:

~~~php
<?php
declare(strict_types = 1);

namespace apex\casino\controller;

abstract class games
{

abstract public function place_bet(int $userid, float $amount);

abstract public function play_hand();

abstract public function win(float $amount);

abstract public function lose(float $amount);

}
~~~

Every child controller created will extends this class, meaning must include the above methods with the same parameters.  This 
allows you to define the standard process flow, while allowing each game to act differently.


### Default Code

Within the child controller, you may define a public property of `$default_code`, which is a BASE64 encoded string, 
and will be used as the default class of every child controller created.  For an example of this, look at the */src/core/controller/notifications.php / http_requests.php* files.


## Child Controllers

Continuing with the above example, we can now create child controllers with:

`php apex.php create controller casino:games:roulette casino`

This will create a new PHP file at /src/casino/controller/games/roulette.php, which extends our above abstract class.  This controller 
will also be owned by the "casino" package, meaning upon publishing the package, this controller will be included.  The owner package of child controllers 
must be defined, as to allow other packages to expand on it, and add child controllers of their own.

For example, you may want to create a "notifications" child controller to send out e-mails regarding game play.  You would do this with:

`php apex.php create controller core:notifications:casino casino`

This will create a PHP file at /src/core/controller/notifications/casino.php, which you can develop as necessary to include any 
merge fields needed for game play.  This controller will also be owned by the "casino" package, hence will not be included in the "core" package, and will only 
be bundled with the "casino" package.

 



