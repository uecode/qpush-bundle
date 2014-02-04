<?php

namespace Uecode\Bundle\QPushBundle\Event;

abstract class Events
{
    const SUBSCRIPTION  = 'uecode_qpush.subscription_change';
    const NOTIFY        = 'uecode_qpush.notification_receieved';
    const RECEIVE       = 'uecode_qpush.message_retrieved';

    final private function __construct() { }
}
