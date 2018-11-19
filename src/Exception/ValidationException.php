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
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationException extends BadRequestHttpException
{
    /**
     * @var ConstraintViolationListInterface
     */
    private $errors;

    /**
     * ValidationException constructor.
     * @param ConstraintViolationListInterface $errors
     * @param string|null $message
     * @param \Exception|null $previous
     * @param int $code
     * @param array $headers
     */
    public function __construct(ConstraintViolationListInterface $errors,
                                string $message = null,
                                \Exception $previous = null,
                                int $code = 0,
                                array $headers = array())
    {
        $this->errors = $errors;
        parent::__construct($message, $previous, $code, $headers);
    }

    /**
     * @return array
     * @throws ClassException
     */
    public function getErrors()
    {
        $errorData = [];
        foreach ($this->errors as $error) {
            if (!($error instanceof ConstraintViolationInterface)) {
                throw new ClassException($error, '$error', ConstraintViolationInterface::class);
            }
            $propertyPath = $error->getPropertyPath();
            $field = \substr($propertyPath, 1, \mb_strlen($propertyPath) - 2);
            if (!\array_key_exists($field, $errorData)) {
                $errorData[$field] = [];
            }
            $errorData[$field][] = $error->getMessage();
        }

        return $errorData;
    }
}