<?php
declare(strict_types = 1);

namespace apex\core\service;

/**
 * Parent abstract class for the service, used to help 
 * control the flow of data to / from adapters, and define the abstract methods required 
 * by adapters.
 */
class notifications
{

    /**
     * Optionally define a BASE64 encoded string here, and this will be used as the default code 
     * for all adapters created for this service.
     * 
     * Use the merge field notifications 
     * for the class name, and it will be replaced appropriately.
     */
    public $default_code = '';



}

