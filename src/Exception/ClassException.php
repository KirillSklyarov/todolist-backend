<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 14.11.2018
 * Time: 21:08
 */

namespace App\Exception;


use Throwable;

class ClassException extends \Exception
{
    /**
     * ClassException constructor.
     * @param $var
     * @param string $varName
     * @param string $targetClassName
     * @param int $code
     * @param Throwable|null $previous
     * @throws \ReflectionException
     */
    public function __construct($var,
                                string $varName,
                                string $targetClassName,
                                int $code = 0,
                                Throwable $previous = null)
    {
        try {
            $reflect = new \ReflectionClass($var);
            $message = sprintf(
                'Var %s is instance of class "%s".',
                $varName, $reflect->getName()
            );
        } catch (\ReflectionException $e) {
            $message = \sprintf(
                'Var %s is type of "%s".',
                $varName, \gettype($var)
            );
        }
        $message = $message . \sprintf(
            ' It should be instance of "%s".',
            $targetClassName);
        parent::__construct($message, $code, $previous);
    }
}