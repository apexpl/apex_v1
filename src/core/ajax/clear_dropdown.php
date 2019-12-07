<?php
declare(strict_types = 1);

namespace apex\core\ajax;

use apex\app;
use apex\svc\redis;
use apex\app\web\ajax;


/** 
 * Clear a dropdown list of all options
 */
class clear_dropdown extends ajax
{
/**
 * Process the AJAX request
 */
public function process()
{ 

    // Set variables
    $badge_id = 'badge_unread_' . app::_post('dropdown');
    $list_id = 'dropdown_' . app::_post('dropdown');
    $recipient = app::get_recipient();
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

