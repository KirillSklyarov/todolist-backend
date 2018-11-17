<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 17.11.2018
 * Time: 20:15
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BaseController extends AbstractController
{
    public function convertJson(Request $request)
    {
        $data = \json_decode($request->getContent(), false);
        if (\json_last_error() !== JSON_ERROR_NONE) {
            throw new BadRequestHttpException(
                'Invalid json body: ' . \json_last_error_msg(),
                null,
                \json_last_error()
            );
        }

        return $data;
    }
}