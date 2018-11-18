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
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class UserController
 * @package App\Controller
 * @Route("/api/v1/user")
 */
class UserController extends BaseController
{
    /**
     * @Route("/create", methods={"POST"}, name="user_create")
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
     * @Route("/register", methods={"POST"}, name="user_register")
     * @param Request $request
     * @param UserRepository $userRepository
     * @param ValidatorInterface $validator
     * @param UserPasswordEncoderInterface $encoder
     * @return JsonResponse
     * @throws ClassException
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\ORM\ORMException
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function register(Request $request,
                             UserRepository $userRepository,
                             ValidatorInterface $validator,
                             UserPasswordEncoderInterface $encoder)
    {
        $user = $this->getUser();
        if (!($user instanceof User)) {
            throw new ClassException($user, '$user', User::class);
        }

        $now = new \DateTime();
        $errors = [];
        $inputData = $this->convertJson($request);
        if (\property_exists($inputData, 'username')
            && 'string' === \gettype($inputData->username)) {
            $user->setUsername($inputData->username);
        } else {
            $errors['username'] = ['Поле username должно присутствовать и иметь тип string'];
        }
        if (property_exists($inputData, 'password')
            && 'string' === \gettype($inputData->password)) {
            $user->setPlainPassword($inputData->password);
        } else {
            $errors['plainPassword'] = ['Поле password должно присутствовать и иметь тип string'];
        }
        if (\count($errors) > 0) {
            throw new ValidationException('Ошибка данных', $errors);
        }
        $user->removeRole(User::ROLE_UNREGISTRED_USER)
            ->addRole(User::ROLE_REGISTRED_USER)
            ->setPermanent(true)
            ->setUpdatedAt($now)
            ->setRegistredAt($now);
        $validatorErrors = $validator->validate($user);
        $this->convertErrors($errors, $validatorErrors);
        if (\count($errors) > 0) {
            throw new ValidationException('Ошибка данных', $errors);
        }
        $existentUser = $userRepository->findOneBy(['username' => $user->getUsername()]);
        if ($existentUser) {
            if (!\array_key_exists('username', $errors)) {
                $errors['username'] = [];
            }
            $errors['username'][] = 'Имя пользователя занято';
            throw new ValidationException('Ошибка данных', $errors);
        }
        $token = (new Token())
            ->setCreatedAt($now)
            ->setLastUsageAt($now)
        ;

        $encoded = $encoder->encodePassword($user, $user->getPlainPassword());
        $user->setPassword($encoded)
            ->clearTokens()
            ->addToken($token);
        $userRepository->update($user);

        return new JsonResponse($token->toArray());
    }

    /**
     * @Route("/info", methods={"GET"}, name="user_info")
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
     * @Route("/login", methods={"POST"}, name="user_login")
     * @param Request $request
     * @param UserPasswordEncoderInterface $encoder
     * @return JsonResponse
     * @throws ClassException
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function login(Request $request,
                          UserRepository $userRepository,
                          UserPasswordEncoderInterface $encoder)
    {
        $message = 'Неверный логин или пароль';
        $now = new \DateTime();
        $errors = [];
        $inputData = $this->convertJson($request);
        if (!(\property_exists($inputData, 'username')
            && 'string' === \gettype($inputData->username))) {
            $errors['username'] = ['Поле username должно присутствовать и иметь тип string'];
        }
        if (!(property_exists($inputData, 'password')
            && 'string' === \gettype($inputData->password))) {
            $errors['plainPassword'] = ['Поле password должно присутствовать и иметь тип string'];
        }
        if (\count($errors) > 0) {
            throw new ValidationException('Ошибка данных', $errors);
        }

        $user = $userRepository->findOneBy(['username' => $inputData->username]);
        if (!$user || !$encoder->isPasswordValid($user, $inputData->password)) {
            throw new UnauthorizedHttpException('Bearer', $message);
        }

        $token = (new Token())
            ->setCreatedAt($now)
            ->setLastUsageAt($now);
        $user->addToken($token)
            ->setLastEnterAt($now);
        $userRepository->update($user);
        return new JsonResponse($token->toArray());
    }

    /**
     * @Route("/logout", methods={"POST"}, name="user_logout")
     * @param TokenRepository $tokenRepository
     * @return JsonResponse
     * @throws ClassException
     * @throws \Exception
     */
    public function logout(TokenRepository $tokenRepository)
    {
        $user = $this->getUser();
        if (!($user instanceof User)) {
            throw new ClassException($user, '$user', User::class);
        }
        $token = $user->getCurrentToken();
        if (!($token instanceof Token)) {
            throw new ClassException($user, '$token', Token::class);
        }
        $tokenRepository->delete($token);

        return new JsonResponse([
            'success' => true
        ]);
    }
}
