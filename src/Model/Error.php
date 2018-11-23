<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 23.11.2018
 * Time: 19:10
 */

namespace App\Model;


use App\Exception\ClassException;

class Error
{
    /**
     * @var integer
     */
    private $code = 0;

    /**
     * @var string
     */
    private $message = 0;

    /**
     * @var ValidationError[]
     */
    private $validationErrors = [];

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @param int $code
     * @return Error
     */
    public function setCode(int $code): Error
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return Error
     */
    public function setMessage(string $message): Error
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @return ValidationError[]
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * @param ValidationError[] $validationErrors
     * @return Error
     */
    public function setValidationErrors(array $validationErrors): Error
    {
        $this->validationErrors = $validationErrors;
        return $this;
    }

    /**
     * @return array
     * @throws ClassException
     */
    public function toArray(): array
    {
        $error = [
            'code' => $this->getCode(),
            'message' => $this->getMessage(),
            'validationErrors' => []
        ];
        foreach ($this->getValidationErrors() as $validationError) {
            if (!($validationError instanceof ValidationError)) {
                throw new ClassException($validationError, '$validationError', ValidationError::class);
            }
            $error['validationErrors'][] = $validationError->toArray();
        }

        return $error;
    }
}