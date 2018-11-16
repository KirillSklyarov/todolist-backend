<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 13.11.2018
 * Time: 21:26
 */

namespace App\Exception;


use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationException extends BadRequestHttpException
{

    private $errors = [];

    /**
     * ValidationException constructor.
     * @param string|null $message
     * @param null|ConstraintViolationListInterface $errors
     * @param \Exception|null $previous
     * @param int $code
     * @param array $headers
     * @throws ClassException
     * @throws \ReflectionException
     */
    public function __construct(string $message = null,
                                $errors = [],
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