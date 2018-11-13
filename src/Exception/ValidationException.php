<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 13.11.2018
 * Time: 21:26
 */

namespace App\Exception;


use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ValidationException extends BadRequestHttpException
{
    private $errors = [];

    public function __construct(string $message = null,
                                array $errors = [],
                                \Exception $previous = null,
                                int $code = 0,
                                array $headers = array())
    {
        $this->errors = $errors;
        parent::__construct($message, $previous, $code, $headers);
    }

    public function getErrors()
    {
        return $this->errors;
    }
}