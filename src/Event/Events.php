<?php

namespace Uecode\Bundle\QPushBundle\Event;

abstract class Events
{
    const ON_NOTIFICATION  = 'on_notification';
    const ON_MESSAGE       = 'message_received';

    final private function __construct() { }

    /**
     * Returns a QPush Notification Event Name
     *
     * @param string $name The name of the Queue for this Event
     *
     * return string
     */
    public static function Notification($name)
    {
        return sprintf('%s.%s', $name, self::NOTIFY);
    }

    /**
     * Returns a QPush Notification Event Name
     *
     * @param string $name The name of the Queue for this Event
     *
     * return string
     */
    public static function Message($name)
    {
        return sprintf('%s.%s', $name, self::MESSAGE);
    }
}
