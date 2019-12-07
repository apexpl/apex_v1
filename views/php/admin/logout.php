<?php
declare(strict_types = 1);

namespace apex\views;

use apex\app;
use apex\svc\auth;
use apex\svc\view;

/**
 * All code below this line is automatically executed when this template is viewed, 
 * and used to perform any necessary template specific actions.
 */


// Logout
auth::logout();

