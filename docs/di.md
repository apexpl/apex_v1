
# Dependency Injection

Apex fully implements PSR11 compliant dependency injection, and uses the [app class](app.md) as the container.  If you are unfamiliar with 
dependency injection, two good starting points can be found at the guide titled [Getting Started - The Dependency Injection Container for Humans](http://php-di.org/doc/getting-started.html), 
and a Youtube talk titled [Demystifying Dependency Injection Containers](https://www.youtube.com/watch?v=y7EbrV4ChJs&t=507s).  This manual assumes 
you are already familiar with depenency injection, and understand its concept.

1. <a href="#container_methods">Container Methods</a>
2. <a href="#injection_methods">Injection Methods</a>
    1. <a href="#constructor_injection">Constructor Injection</a>
    2. <a href="#method_injection">Method Injection</a>
    3. <a href="#annotation_injection">Addnotation Injection</a>


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
            $user = app::makeset(user::class, ['id'] => $default_userid]);
        }

        // Get the store
        $store = app::get(store::class);
        $store->check_if_open();

        // Call the add() method on order class
        app::call([order::class, 'add'], ['note' => 'Some test note']);

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




