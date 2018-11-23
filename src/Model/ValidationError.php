<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 23.11.2018
 * Time: 18:52
 */

namespace App\Model;


class ValidationError
{
    /**
     * @var string
     */
    private $field;

    /**
     * @var string[]
     */
    private $errors = [];

    /**
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @param string $field
     * @return ValidationError
     */
    public function setField(string $field): ValidationError
    {
        $this->field = $field;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param string[] $errors
     * @return ValidationError
     */
    public function setErrors(array $errors): ValidationError
    {
        $this->errors = $errors;
        return $this;
    }

    /**
     * @param string $error
     * @return ValidationError
     */
    public function addError(string $error): ValidationError
    {
        $this->errors[] = $error;
        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'field' => $this->getField(),
            'errors' => $this->getErrors()
        ];
    }
}