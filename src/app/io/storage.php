<?php
declare(strict_types = 1);

namespace apex\app\io;

use apex\app;
use apex\svc\redis;
use apex\svc\debug;


/**
 * Remote File Storage Handler
 *
 * Service: apex\svc\storage
 *
 * Handles the management of file storage via the remote/flysystem package, 
 * allowing for files to be distributed amongst multiple servers / services such as AWS.
 *
 * This class is available within the services container, meaning its methods can be accessed statically via the 
 * service singleton as shown below.
 *
 * PHP Example
 * --------------------------------------------------
 * 
 * <?php
 * 
 * namespace apex;
 * 
 * use apex\app;
 * use apex\svc\storage;
 *
 (
 ( // Set cache item
 * cache::set('some_id', 'my item contents');
 *
 * // Get cache item
 * $data = cache::get('some_id');
 *
 */

