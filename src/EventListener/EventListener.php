<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 13.11.2018
 * Time: 21:21
 */

namespace App\EventListener;

use App\Exception\ValidationException;
use App\Model\ApiResponse;
use App\Model\Error;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class EventListener
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        // Don't do anything if it's not the master request.
        if (!$event->isMasterRequest()) {
            return;
        }

        if ('OPTIONS' === $event->getRequest()->getRealMethod()) {
            $event->setResponse(new JsonResponse());
        }
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        // TODO implement log
        $response = $event->getResponse();
//        $response->setStatusCode(200);
        $responseHeaders = $response->headers;

        $responseHeaders->set('Access-Control-Allow-Headers', 'origin, content-type, accept, x-auth-token');
        $responseHeaders->set('Access-Control-Allow-Origin', '*');
        $responseHeaders->set('Access-Control-Allow-Methods', 'POST, GET, PUT, DELETE, PATCH, OPTIONS');
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        try {
            // TODO logger
            $error = new Error();
            $apiResponse = new ApiResponse(null, $error, false);

            if ($exception instanceof HttpExceptionInterface) {
                $error->setCode($exception->getStatusCode());
                $error->setMessage($exception->getMessage());
                $apiResponse->setStatusCode($exception->getStatusCode());
                if ($exception instanceof ValidationException) {
                    $error->setValidationErrors($exception->getErrors());
                }
            } else {
                $apiResponse->setStatusCode(500);
                $error->setCode($exception->getCode());
                if ($this->container->get('kernel')->getEnvironment() === 'dev') {
                    $error->setMessage($exception->getMessage());
                }
            }
//            $apiResponse = new ApiResponse(null, $error, false);
            $event->setResponse($apiResponse);
        } catch (\Exception $exception) {
            $jsonResponse = new JsonResponse(
                [
                    'success' => false,
                    'error' => [
                        'code' => 500,
                        'message' => Response::$statusTexts[500],
                    ],
                    'data' => null
                ]
            );
            $jsonResponse->setStatusCode(500);
            $event->setResponse($jsonResponse);
        }
    }
}