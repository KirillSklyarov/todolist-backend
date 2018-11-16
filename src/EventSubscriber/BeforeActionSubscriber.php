<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 13.11.2018
 * Time: 20:55
 */

namespace App\EventSubscriber;


use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class BeforeActionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::CONTROLLER => 'convertJson',
        );
    }

    public function convertJson(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        if ($request->getContentType() !== 'json' || !$request->getContent()) {
            return;
        }
        $data = \json_decode($request->getContent(), true);
        if (\json_last_error() !== JSON_ERROR_NONE) {
            throw new BadRequestHttpException(
                'Invalid json body: ' . \json_last_error_msg(),
                null,
                \json_last_error()
            );
        }
        $request->request->replace(\is_array($data) ? $data : []);

    }
}