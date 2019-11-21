<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\Response;

class ControllerListener
{
    public function onKernelController(\Symfony\Component\HttpKernel\Event\FilterControllerEvent $evt)
    {
        //die('I catch you!!!');
    }
}