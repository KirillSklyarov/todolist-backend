<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 17.11.2018
 * Time: 20:15
 */

namespace App\Controller;

use App\Exception\ClassException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class BaseController extends AbstractController
{
    protected function convertJson(Request $request)
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

    /**
     * @param array $errors
     * @param ConstraintViolationListInterface $validatorErrors
     * @throws ClassException
     */
    protected function convertErrors(array &$errors,
                                     ConstraintViolationListInterface $validatorErrors)
    {
        foreach ($validatorErrors as $validatorError) {
            if (!($validatorError instanceof ConstraintViolationInterface)) {
                throw new ClassException($validatorError, '$validatorError', ConstraintViolationInterface::class);
            }
            $field = $validatorError->getPropertyPath();
            if (!\array_key_exists($field, $errors)) {
                $errors[$field] = [];
            }
            $errors[$field][] = $validatorError->getMessage();
        }
    }
}