<?php
declare(strict_types = 1);

namespace apex\core\ajax;

use apex\app;
use apex\svc\redis;


class clear_dropdown 
{




    /**
     * Processes the AJAX function, and uses the moethds within the 'apex\ajax' 
     * class to modify the DOM elements within the web browser.  See documentation 
     * for durther details. 
     */




public function process()
{ 

    // Set variables
    $badge_id = 'badge_unread_' . app::_post('dropdown');
    $list_id = 'dropdown_' . app::_post('dropdown');
    $recipient = (app::get_area() == 'admin' ? 'admin:' : 'user:') . app::get_userid();
    $clearall = app::_post('clearall') ?? 0;

    // Reset redis
    $redis_key = 'unread:' . app::_post('dropdown');
    redis::hset($redis_key, $recipient, 0);

    // Perform necessary actions
    $this->set_text($badge_id, 0);
    $this->set_display($badge_id, 'none');

    // Clear list, if needed
    if ($clearall == 1) { 
        $redis_key = app::_post('dropdown') . ':' . $recipient;
        redis::del($redis_key);
        $this->clear_list($list_id);
    }

}


}

