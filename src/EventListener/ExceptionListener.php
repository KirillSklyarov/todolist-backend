<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 13.11.2018
 * Time: 21:21
 */

namespace App\EventListener;

use App\Exception\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        // You get the exception object from the received event
        $exception = $event->getException();
        $message = sprintf(
            'My Error says: %s with code: %s',
            $exception->getMessage(),
            $exception->getCode()
        );

        // Customize your response object to display the exception details
        $response = new JsonResponse();
        $content = ['message' => $message];


        // HttpExceptionInterface is a special type of exception that
        // holds status code and header details
        if ($exception instanceof HttpExceptionInterface) {
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->replace($exception->getHeaders());
            if ($exception instanceof ValidationException) {
                $content['errors'] = $exception->getErrors();
            }
        } else {
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $response->setContent(
            $content
        );
        $event->setResponse($response);
    }
}