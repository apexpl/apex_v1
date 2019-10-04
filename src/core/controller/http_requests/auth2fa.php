<?php
declare(strict_types = 1);

namespace apex\core\controller\http_requests;

use apex\app;
use apex\svc\redis;


class auth2fa
{

/**
 * Process a 2FA request 
 */
public function process()
{ 

    // Check for hash
    $hash = (string) trim(app::get_uri_segments()[0]) ?? '';
    $redis_key = '2fa:email:' . hash('sha512', trim($hash));

    // Check for key
    if ($data = redis::get($redis_key)) { 
        $vars = json_decode($data, true);
    } else { 
        app::echo_template('2fa_nohash');
    }

    // Delete from redis
    redis::del($redis_key);

    // Update redis session, if needed
    if ($vars['is_login'] == 1) { 
        $redis_key = 'auth:' . $vars['auth_hash'];
        redis::hset($redis_key, '2fa_status', 1);
        $vars['uri'] = 'members/index';
    }

    // Verify the request
    app::verify_2fa($vars);

}


}

