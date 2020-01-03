
# Service Provider / Adapter Components

&nbsp; | &nbsp;
------------- |-------------
**Description:** | Used to implement a standardized flow for a process that differs depending on some type / adapter.  For example, http_requests are a service provider that handle all incoming HTTP requests to Apex depending on the first element of the URI.  Another is the various transaction types available (deposit, withdraw, fee, commission, membership_fee) which all need to follow the same logic, but handle the transactions slightly differently depending on transaction type / adapter.
**Create Command:** Service Provider: `./apex create service PACKAGE:ALIAS`<br />Adapter: `./apex create adapter PACKAGE:SERVICE_ALIAS:ALIAS OWNER`
**File Location:** | Service Adapter: /src/PACKAGE/service/ALIAS.php<br />Adapter: /src/PACKAGE/service/SERVICE_ALIAS/ALIAS.php
**Namespace:** | Service Provider: `\apex\PACKAGE\service\ALIAS`<br />Adapter: `\apex\PACKAGE\service\SERVICE_ALIAS\ALIAS`


## Service Provider

This is the parent class for the service provider, and all adapters will extend it.  This class will contain any overall methods / properties 
used, which the adapters may also take advantage of.  A good example of this is the file 
located at */src/transaction/service/transaction.php* which handles the processing of a ransaction, but each transaction may be processed a little differently 
pending on the type / adapter.

For example, say you're doing a gambling project of some kind with different games.  Each game needs to follow the same general process 
flow, but handles things like bets, wins and losses differently.  You could create a service provider for this with:

`./apex create service casino:games`

This assumes you're developing the package "casino", and would place a new PHP file at /src/casino/service/games.php where you can 
define the various parent and abstract methods.  For example:

~~~php
<?php
declare(strict_types = 1);

namespace apex\casino\service;

abstract class games
{

abstract public function place_bet(int $userid, float $amount);

abstract public function play_hand();

abstract public function win(float $amount);

abstract public function lose(float $amount);

}
~~~

Every adapter created will extends this class, meaning must include the above methods with the same parameters.  This 
allows you to define the standard process flow, while allowing each game to act differently.

##### Default Code

Within the service provider, you may define a public property of `$default_code`, which is a BASE64 encoded string, 
and will be used as the default class of every adapter created.  For an example of this, look at the */src/core/service/notifications.php / http_requests.php* files.


## Adapters

Continuing with the above example, we can now create child controllers with:

`./apex create adapter casino:games:roulette casino`

This will create a new PHP file at /src/casino/service/games/roulette.php, which extends our above abstract class.  This adapter 
will also be owned by the "casino" package, meaning upon publishing the package, this adapter will be included.

For another example, you may want to create a "notifications" adapter to send out e-mails regarding game play.  You would do this with:

`./apex create adapter core:messages:casino casino`

This will create a PHP file at /src/core/service/messages/casino.php, which you can develop as necessary to include any 
merge fields needed for game play.  This controller will also be owned by the "casino" package, hence will not be included in the "core" package, and will only 
be bundled with the "casino" package.

 



