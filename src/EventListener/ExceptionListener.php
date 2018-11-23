<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 13.11.2018
 * Time: 21:21
 */

namespace App\EventListener;

use App\Exception\ClassException;
use App\Exception\ValidationException;
use App\Model\ApiResponse;
use App\Model\Error;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        try {
            // TODO logger
            $error = (new Error())->setCode($exception->getCode());
            if ($exception instanceof HttpExceptionInterface) {
                $error->setMessage($exception->getMessage());
                if ($exception instanceof ValidationException) {
                    $error->setValidationErrors($exception->getErrors());
                }
            } elseif ($this->container->get('kernel')->getEnvironment() === 'dev') {
                $error->setMessage($exception->getMessage());
            }
            $apiResponse = new ApiResponse(null, $error, false);
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
            $event->setResponse($jsonResponse);
        }
    }
}