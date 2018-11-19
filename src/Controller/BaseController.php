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
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

class BaseController extends AbstractController
{
    const INPUT_DATA_ERROR = 'Input data error';
    const MESSAGE_MIN_LENGHT = 'minLenght';
    const MESSAGE_MAX_LENGHT = 'maxLenght';
    const MESSAGE_PATTERN = 'regexp';
    const MESSAGE_DATE = 'date';
    const MESSAGE_INTEGER = 'integer';

    protected function convertJson(Request $request)
    {
        $data = \json_decode($request->getContent(), true);
        if (\json_last_error() !== JSON_ERROR_NONE) {
            throw new BadRequestHttpException(
                'Invalid json body: ' . \json_last_error_msg(),
                null,
                \json_last_error()
            );
        }

        return $data;
    }

    protected function validateUser($input)
    {
        $validator = Validation::createValidator();
        $constraint = new Assert\Collection(
            [
                'username' => new Assert\Regex(
                    [
                        'pattern' => '/^[\w.\-]+$/'
                    ]
                )
            ]
        );

        $violations = $validator->validate($input, $constraint);

        return $violations;
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