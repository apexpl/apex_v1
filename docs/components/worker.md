
# Workers and Routing Keys

&nbsp; | &nbsp;
------------- |-------------
**Description:** | Used to receive and process incoming messages from RabbitMQ.  See the [Messaging (RabbitMQ)](../messaging.md) page for more details about RabbitMQ and horizontal scaling.
**Create Command:** | `php apex.php create worker PACKAGE:ALIAS ROUTING_KEY_ALIAS`
**File Location:** | /src/PACKAGE/worker/ALIAS.php
**Namespace:** | `apex\PACKAGE\worker`


## Routing Keys

When creating a worker you need to define a routing key alias, which is used within the routing key upon sending / receiving messages to / form RabbitMQ.  This can 
be anything you want, and the resulting routing key to call a method within the worker will be *PACKAGE.ROUTING_KEY_ALIAS.METHOD*.  

For example, a worker was created with `php apex.php create worker users:user users.profile`, meaning the PHP class file created 
is located at */src/users/worker/user.php*.  When any messages are sent to RabbitMQ with the routing key users.profile.*, this PHP class will be loaded, and the 
method name of the third segment of the routing key will be executed.

For example, upon creating a new user, a message is sent via RabbitMQ to *users.profile.create*, which loads this worker class and executes the 
`create()` method within it.  


## Worker Daemon

If you're either running Apex on a single server, or this is a back-end application server, you need to run the Apex daemon.  There is a bash script located at /src/apex, which is 
the init script for all Apex processes -- one-way messaging, two-way RPC calls, and the WebSocket server.  Copy it over to the /etc/init.d/ directory of your server, CHMOD it to 0755, and with a non-root sudo user execute it to start the 
Apex servers.  This is required in order for the messages sent to RabbitMQ to be relayed properly to the back-end application server(s), even if everything is running on the same server.

For example, change to the installation directory of Apex, and type:

~~~
sudo cp src/apex /etc/init.d/
sudo chmod 0755 /etc/init.d/apex
sudo /etc/init.d/apex start
~~~



