<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 15.11.2018
 * Time: 22:13
 */

namespace App\Util;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

trait JsonConverter
{
    private function convert(string $content)
    {
        $data = \json_decode($content, true);
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