<?php

use apex\app\interfaces\LoggerInterface;
use apex\app\interfaces\DebuggerInterface;
use apex\app\interfaces\DBInterface;
use apex\app\interfaces\msg\DispatcherInterface;
use apex\app\interfaces\ViewInterface;
use apex\app\interfaces\AuthInterface;
use apex\app\interfaces\CacheInterface;
use apex\app\msg\emailer;
use apex\app\sys\components;
use apex\app\sys\encrypt;
use apex\app\utils\date;
use apex\app\utils\forms;
use apex\app\utils\hashes;
use apex\app\utils\images;
use apex\app\utils\geoip;
use apex\app\io\io;
use apex\app\io\cache;
use apex\app\io\storage;



return [
    DBInterface::class => [apex\app\db\mysql::class], 
    LoggerInterface::class => [apex\app\sys\log::class, ['channel_name' => 'apex']], 
    DebuggerInterface::class => [apex\app\sys\debug::class], 
    DispatcherInterface::class => [apex\app\msg\dispatcher::class, ['channel_name' => 'apex']],
    ViewInterface::class => [apex\app\web\view::class], 
    AuthInterface::class => [apex\app\sys\auth::class], 
    CacheInterface::class => [apex\app\io\cache::class],  

    emailer::class => [apex\app\tests\test_emailer::class], 
    components::class => [components::class], 
    date::class => [date::class], 
    encrypt::class => [encrypt::class], 
    forms::class => [forms::class], 
    hashes::class => [hashes::class], 
    images::class => [images::class], 
    io::class => [io::class], 
    storage::class => [storage::class],  
    geoip::class => [geoip::class], 
    'nexmo_api_url' => ['var', 'http://~domain_name~/unit_test/nexmo?']
];


