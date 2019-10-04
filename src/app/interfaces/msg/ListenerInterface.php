<?php
declare(strict_types=1);

namespace apex\app\interfaces\msg;

use apex\app\interfaces\msg\EventMessageInterface;


/**
 * Mapper from an event to the listeners that are applicable to that event.
 */
interface ListenerInterface
{
    /**
     *   An event for which to return the relevant listeners.
     * 
     * @param EventMessageInterface $event The event message object to get listners for.
     *
     * @return iterable[callable]
     *   An iterable (array, iterator, or generator) of callables.  Each
     *   callable MUST be type-compatible with $event.
     */
    public function getListenersForEvent(EventMessageInterface $event) : iterable;
}
