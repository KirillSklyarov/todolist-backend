<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 22.11.2018
 * Time: 23:12
 */

namespace App\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class ResponseListener
{
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // TODO implement log
        $response = $event->getResponse();
        $response->setStatusCode(200);
        $responseHeaders = $response->headers;

        $responseHeaders->set('Access-Control-Allow-Headers', 'origin, content-type, accept, x-auth-token');
        $responseHeaders->set('Access-Control-Allow-Origin', '*');
        $responseHeaders->set('Access-Control-Allow-Methods', 'POST, GET, PUT, DELETE, PATCH, OPTIONS');
    }
}