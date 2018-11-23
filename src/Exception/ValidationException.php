<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 13.11.2018
 * Time: 21:26
 */

namespace App\Exception;


use App\Model\ValidationError;
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
     */
    public function __construct(ConstraintViolationListInterface $errors,
                                string $message = 'Input data error',
                                \Exception $previous = null)
    {
        $this->errors = $errors;
        parent::__construct($message, $previous, 400, []);
    }

    /**
     * @return ValidationError[]
     * @throws ClassException
     */
    public function getErrors(): array
    {
        $validationErrors = [];
        foreach ($this->errors as $error) {
            if (!($error instanceof ConstraintViolationInterface)) {
                throw new ClassException($error, '$error', ConstraintViolationInterface::class);
            }
            $propertyPath = $error->getPropertyPath();
            $field = \substr($propertyPath, 1, \mb_strlen($propertyPath) - 2);
            $found = \array_filter($validationErrors,
                function ($validationError) use ($field) {
                    if (!($validationError instanceof ValidationError)) {
                        throw new ClassException($validationError, '$validationError', ValidationError::class);
                    }
                    return $validationError->getField() === $field;
                });
            if (count($found) === 0) {
                $validationErrors[] = (new ValidationError())
                    ->setField($field)
                    ->addError($error->getMessage());
            } else {
                $validationError = current($found);
                if (!($validationError instanceof ValidationError)) {
                    throw new ClassException($validationError, '$validationError', ValidationError::class);
                }
                $validationError->addError($error->getMessage());
            }
        }

        return $validationErrors;
    }
}