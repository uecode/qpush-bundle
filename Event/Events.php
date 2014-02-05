<?php

namespace Uecode\Bundle\QPushBundle\Event;

abstract class Events
{
    const SUBSCRIPTION  = 'uecode_qpush.subscription';
    const NOTIFY        = 'uecode_qpush.notify';
    const MESSAGE       = 'uecode_qpush.message';

    final private function __construct() { }
}
