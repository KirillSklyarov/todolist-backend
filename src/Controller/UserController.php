<?php

namespace App\Controller;

use App\Entity\Token;
use App\Entity\User;
use App\Exception\ClassException;
use App\Exception\ValidationException;
use App\Model\ApiResponse;
use App\Repository\TokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class UserController
 * @package App\Controller
 * @Route("/api/v1/user")
 */
class UserController extends BaseController
{

    public static function validate(array $input): ConstraintViolationListInterface
    {
        $validator = Validation::createValidator();
        $collection = [
            'username' => [
                new Assert\Length([
                    'min' => 2,
                    'minMessage' => self::MESSAGE_MIN_LENGHT,
                    'max' => 32,
                    'maxMessage' => self::MESSAGE_MAX_LENGHT
                ]),
                new Assert\Regex([
                    'pattern' => '/^[\w.\-]+$/',
                    'message' => self::MESSAGE_PATTERN
                ])
            ],
            'password' => [
                new Assert\Length([
                    'max' => 64,
                    'maxMessage' => self::MESSAGE_MAX_LENGHT
                ]),
                new Assert\Regex([
                    'pattern' => '/^[\w!@#$%^&*()<>\-=+.,.?]+$/',
                    'message' => self::MESSAGE_PATTERN
                ])
            ]
        ];
        $constraint = new Assert\Collection($collection);
        $violations = $validator->validate($input, $constraint);

        return $violations;
    }

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
            ->setLastUsageAt($now);
        $user = (new User())
            ->addToken($token)
            ->addRole(User::ROLE_UNREGISTRED_USER)
            ->setPermanent(false)
            ->setCreatedAt($now)
            ->setUpdatedAt($now)
            ->setLastEnterAt($now);

        $userRepository->create($user);

        return new ApiResponse($token->toArray());
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

        $inputData = $this->convertJson($request);
        $errors = self::validate($inputData);

        if (\count($errors) > 0) {
            throw new ValidationException($errors, self::INPUT_DATA_ERROR);
        }
        $existentUser = $userRepository->findOneBy(['username' => $inputData['username']]);
        if ($existentUser) {
            throw new BadRequestHttpException('Username is existing');
        }
        $now = new \DateTime();
        $token = (new Token())
            ->setCreatedAt($now)
            ->setLastUsageAt($now);
        $encoded = $encoder->encodePassword($user, $inputData['password']);
        $user->setUsername($inputData['username'])
            ->setPlainPassword($inputData['password'])
            ->setPassword($encoded)
            ->removeRole(User::ROLE_UNREGISTRED_USER)
            ->addRole(User::ROLE_REGISTRED_USER)
            ->clearTokens()
            ->addToken($token)
            ->setPermanent(true)
            ->setUpdatedAt($now)
            ->setRegistredAt($now)
            ->setLastEnterAt($now)
        ;
        $userRepository->update($user);

        return new ApiResponse($token->toArray());
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
        return new ApiResponse($user->toArray());

    }

    /**
     * @Route("/login", methods={"POST"}, name="user_login")
     * @param Request $request
     * @param UserRepository $userRepository
     * @param UserPasswordEncoderInterface $encoder
     * @return JsonResponse
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Exception
     */
    public function login(Request $request,
                          UserRepository $userRepository,
                          UserPasswordEncoderInterface $encoder)
    {
        $now = new \DateTime();
        $inputData = $this->convertJson($request);
        $errors = self::validate($inputData);
        if (\count($errors) > 0) {
            throw new ValidationException($errors, self::INPUT_DATA_ERROR);
        }

        $user = $userRepository->findOneBy(['username' => $inputData['username']]);
        if (!$user || !$encoder->isPasswordValid($user, $inputData['password'])) {
            throw new UnauthorizedHttpException('Bearer', 'Wrong username or password');
        }
        $token = (new Token())
            ->setCreatedAt($now)
            ->setLastUsageAt($now);
        $user->addToken($token)
            ->setLastEnterAt($now)
            ->setCurrentToken($token)
        ;
        $userRepository->update($user);

        return new ApiResponse($token->toArray());
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

        return new ApiResponse(null);
    }
}
