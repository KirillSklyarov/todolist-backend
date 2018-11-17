<?php

namespace App\Controller;

use App\Entity\Token;
use App\Entity\User;
use App\Exception\ClassException;
use App\Exception\ValidationException;
use App\Repository\TokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class UserController
 * @package App\Controller
 * @Route("/api/v1")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/user/create", methods={"POST"}, name="user_create")
     * @param UserRepository $userRepository
     * @return JsonResponse
     * @throws \Exception
     */
    public function create(UserRepository $userRepository)
    {
        $now = new \DateTime();
        $token = (new Token())
            ->setCreatedAt($now)
            ->setLastUsageAt($now)
        ;
        $user = (new User())
            ->addToken($token)
            ->addRole(User::ROLE_UNREGISTRED_USER)
            ->setPermanent(false)
            ->setCreatedAt($now)
            ->setUpdatedAt($now)
            ->setLastEnterAt($now)
        ;

        $userRepository->create($user);

        return new JsonResponse($token->toArray());
    }

    /**
     * @Route("/user/register", methods={"POST"}, name="user_register")
     * @param Request $request
     * @param UserRepository $userRepository
     * @param TokenRepository $tokenRepository
     * @return JsonResponse
     * @throws \Exception
     */
    public function register(Request $request,
                             UserRepository $userRepository,
                             TokenRepository $tokenRepository,
                             ValidatorInterface $validator,
                             UserPasswordEncoderInterface $encoder)
    {

        $user = $this->getUser();
        if (!($user instanceof User)) {
            throw new ClassException($user, '$user', User::class);
        }

        $now = new \DateTime();
        $errors = [];
        $user->setUsername($request->get('username'))
            ->setPlainPassword($request->get('password'))
            ->removeRole(User::ROLE_UNREGISTRED_USER)
            ->addRole(User::ROLE_REGISTRED_USER)
            ->setPermanent(true)
            ->setUpdatedAt($now)
            ->setRegistredAt($now);
        $validatorErrors = $validator->validate($user);
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
        $existentUser = $userRepository->findOneBy(['username' => $user->getUsername()]);
        if ($existentUser) {
            if (!\array_key_exists('username', $errors)) {
                $errors['username'] = [];
            }
            $errors['username'][] = 'Имя пользователя занято';
        }
        if (\count($errors) > 0) {
            throw new ValidationException('Ошибка данных', $errors);
        }

        $token = (new Token())
            ->setCreatedAt($now)
            ->setLastUsageAt($now)
            ->setUser($user)
        ;

        $encoded = $encoder->encodePassword($user, $user->getPlainPassword());
        $user->setPassword($encoded);
        $userRepository->update($user);
        $tokenRepository->create($token);
        $tokenRepository->deleteOld($now, $user);

        return new JsonResponse($token->toArray());
    }

    /**
     * @Route("/user/info", methods={"GET"}, name="user_info")
     * @return JsonResponse
     * @throws ClassException
     */
    public function info()
    {
        $user = $this->getUser();
        if (!($user instanceof User)) {
            throw new ClassException($user, '$user', User::class);
        }
        return new JsonResponse($user->toArray());

    }

    /**
     * @Route("/user/login", methods={"POST"}, name="user_login")
     * @param EntityManagerInterface $em
     * @param UserPasswordEncoderInterface $encoder
     * @return JsonResponse
     * @throws ClassException
     */
    public function login(EntityManagerInterface $em,
                          UserPasswordEncoderInterface $encoder)
    {
        $user = $this->getUser();
        if (!($user instanceof User)) {
            throw new ClassException($user, '$user', User::class);
        }
        $token = $user->getCurrentToken();
        if (!($token instanceof Token)) {
            throw new ClassException($token, '$token', Token::class);
        }
        return new JsonResponse($token->toArray());
    }
}
