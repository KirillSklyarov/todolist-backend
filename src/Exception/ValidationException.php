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
                                ?ConstraintViolationListInterface $errors = null,
                                \Exception $previous = null,
                                int $code = 0,
                                array $headers = array())
    {
        $errorsData = [];

        if ($errors instanceof ConstraintViolationListInterface) {
            foreach ($errors as $error) {
                if (!($error instanceof ConstraintViolationInterface)) {
                    throw new ClassException($error, '$error', ConstraintViolationInterface::class);
                }
                $field = $error->getPropertyPath();
                if (!\array_key_exists($field, $errorsData)) {
                    $errorsData[$field] = [];
                }
                $errorsData[$field][] = $error->getMessage();
            }
        }

        $this->errors = $errorsData;
        parent::__construct($message, $previous, $code, $headers);
    }

    public function getErrors()
    {
        return $this->errors;
    }
}