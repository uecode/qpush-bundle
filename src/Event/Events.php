<?php

namespace Uecode\Bundle\QPushBundle\Event;

abstract class Events
{
    const SUBSCRIPTION  = 'subscription';
    const NOTIFY        = 'notify';
    const MESSAGE       = 'message';

    final private function __construct() { }

    /**
     * Returns a QPush Notificaiton Event Name
     *
     * @param string $name The name of the Queue for this Event
     *
     * return string
     */
    static public function Notify($name)
    {
        return sprintf('%s.%s', $name, self::NOTIFY);
    }

    /**
     * Returns a QPush Notificaiton Event Name
     *
     * @param string $name The name of the Queue for this Event
     *
     * return string
     */
    static public function Subscription($name)
    {
        return sprintf('%s.%s', $name, self::SUBSCRIPTION);
    }
    
    /**
     * Returns a QPush Notificaiton Event Name
     *
     * @param string $name The name of the Queue for this Event
     *
     * return string
     */
    static public function Message($name)
    {
        return sprintf('%s.%s', $name, self::MESSAGE);
    }
}
