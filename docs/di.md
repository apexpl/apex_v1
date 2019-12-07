
# Dependency Injection

Apex fully implements PSR11 compliant dependency injection, and uses the [app class](app.md) as the container.  If you are unfamiliar with 
dependency injection, a good starting guide can be found at: [Getting Started - The Dependency Injection Container for Humans](http://php-di.org/doc/getting-started.html)

1. <a href="#overview">Overview</a>
2. <a href="#container_methods">Container Methods</a>
3. <a href="#injection_methods">Injection Methods</a>
    1. <a href="#constructor_injection">Constructor Injection</a>
    2. <a href="#method_injection">Method Injection</a>
    3. <a href="#annotation_injection">Addnotation Injection</a>



<a name="overview"></a>
## Overview

To put it simply, dependency injection (DI for short) changes the software so instead of constantly giving
"tools" to your classes by passing parameters to them or always creating new instances of classes, you simply
provide a toolbox and allow each class to pick out what it needs, as it needs it.  Instead of you always
passing off tools to each class / method, the classes just specify what they need and it is provided
(injected) to them.


#### Example

You should always try to load classes via the container in case they either have dependencies themselves, or
will be injected into other classes during execution.  For an example, say we have a user and store objects,
both of which are required by an orders class.  Somewhere in our code we would create our two objects such as:

~~~php
namespace apex;

use apex\app;
use apex\users\user;
use apex\retail\store;

// Some variables
$userid = 847;
$store_id = 23;

// Define our user, and set in container
$user = app::make(user::class, ['id' => $userid]);
$user->modify_something();
app::set(user::class, $user);

// Define our store, and set in container
$store = app::make(store::class, ['store_id' => $store_id]);
app::set(store::class, $store);
~~~

Instead of creating a new instance of the user class with `$user = new user()` we created it via the
container's make() method.  This is done so any dependencies the user class has will be automatically
injected to it.  We also set both classes via the set() method, and they are now sitting in our container
ready for injection.

Now when a new order needs to be created, we will need both the user and store objects, plus say an emailer
class.  It could look something like:

~~~php
namespace apex\cart;

use apex\app;
use apex\users\user;
use apex\retail\store;
use apex\app\msg\emailer;

class order
{

    // Properties
    private $user;
    private $store;
    private $emailer;
    private $note;
    /**
     * Constructor.  Grab our dependencies.
     */
    public function __construct(user $user, store $store, emailer $emailer, string $note = '')
    {

        // Set properties
        $this->user = $user;
        $this->store = $store;
        $this->emailer = $emailer;
        $this->note = note;

    }

    /**
     * Add order
     */
    public function add()
    {

        // Load user profile
        $profile = $this->user->load();

        // Send e-mails
        $this->emailer->process_emails();

        // Add order //
        $order_id = // do something //

        // Return
        return $order_id;

    }

}
~~~

Now when we want to add a new order, we can load the class and call the add() method directly with:

~~~php
namespace apex;

use apex\app;
use apex\cart\order;

// Add new order
$order_id = app::call([order::class, 'add'], ['note' => 'some order note']);
~~~

The above will create a new instance of the order class, and automatically inject the user and store objects
we previously defined into the constructor.  Plus since we defined the emailer class within our "use"
statements in the order class, it will be injected into the constructor as well.  if an emailer object has
already been defined in the container it will be used, and otherwise a new instance of the emailer class will
be created.

That's dependency injection in a nutshell.  Instead of specifically passing the user and store objects to the
orders class, the orders class simply grabs them from our container as needed.  Plus since we defined the
emailer class in our "use" statements, it will be injected as well.


<a name="container_methods"></a>
## Container Methods

There are a handful of methods available within the [app class](app.md) to manage the items within the
dependency injection container, and are listed below.

method | Description 
------------- |-------------
get($key) | Get the value of the key from the container.
has($key) | Returns a boolean as to whether or not the container contains the specified key.
set($key, $value) | Set a new value within the container.
make($class_name, array $params = []) | Creates and returns an instance of the specified class.  This instance is not saved within the container, and is only created with dependency injection, then returned.
makeset($class_name, array $params = []) | Simliar to the make() method, except it also sets the returned object into the container.
call([$class_name, $method], array $params = []) | Call a specific method within a class while performing dependency injection directly on the method.  This does not save anything within the container, and only calls the method, but does allow for method / setter injection.


**Example*

~~~php
namespace apex;

use apex\app;
use apex\retail\store;
use apex\cart\order;
use apex\users\user;
use apex\app\msg\emailer;


class some_class
{

    /**
     * @Inject
     * @var emailer
     */
    private $emailer;

    public function __construct(app $app)
    {

        // Create a user class, if we don't have one
        if (!app::has(user::class)) {
            $user = app::make(user::class, ['id'] => $default_userid]);
            app::set(user::class, $user);
        }

        // Get the store
        $store = app::get(store::class);
        $store->check_if_open();

        // Call the add() method on order class
        app::call([order::class, 'add']);

        // Call process() on emailer
        $this->emailer->process();

    }

}
~~~


<a name="injection+methods"></a>
## Injection Methods

Apex supports three different injection methods -- constructor, method, and annotations. All three methods are
described below with examples.


<a name="constructor_injection"></a>
#### Constructor Injection

This is basically exactly what it sounds like -- dependencies are injected into the `__construct()` method
upon class instanciation.  The class must be loaded via the container for injection to work, and all injected
classes must be defined within "use" statements.  For example:

~~~php
namespace apex;

use apex\app;
use apex\users\user;
use apex\blog\blog_post;

class myclass
{

    // Properties
    private $user;
    private $blog_post;

    public function __construct(user $user, blog_post $blog_post)
        {
            $this->user = $user;
            $this->blog_post = $blog_post;
        }
    }
}
~~~


<a name="method_injection"></a>
#### Method Injection

Similar to constructor injection, but instead of injection only occurring on the `__construct()` method
during class instanciation, injection will occur directly on the method upon calling it.  Please note, method
injection only works when calling the method via the container using the
call() method.

**NOTE:** All methods within listeners support method injection.  This is explained more on the [Event
Listeners](event_listeners.md) page.

~~~php
namespace apex;

use apex\app;
use apex\users\user;
use apex\cart\product;

class myclass {

    function add(product $prod) {

        // do something //

    }
}
~~~

This must then be called via the call() method of the container, such as:

~~~php
app::call([myclass::class, 'add']);
~~~


<a name="annotation_injection"></a>
#### Annotation Injection

Another method of injection is via annotations within comments, and directly inject properties with different
object dependencies.  This is done using the `@Inject` and `@var` tags within comments, such as for example:

~~~
namespace apex;

use apex\app;
use apex\app\msg\emailer;

class myclass {

    /**
     * @Inject
     * @var app
     */
    private $app;

    /**
     * @Inject
     * @var emailer
     */
    private $emailer;

    /// Rest of the class //

}
~~~

When the class is loaded via the container, those two properties will be automatically injected with the app
and emailer objects respectively.




