<?php

namespace App\Controller;

use App\Entity\Token;
use App\Entity\User;
use App\Exception\ClassException;
use App\Exception\ValidationException;
use App\Repository\TokenRepository;
use App\Repository\UserRepository;
use Ramsey\Uuid\Uuid;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class UserController
 * @package App\Controller
 * @Route("/api/v1")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/user/create", methods={"POST"}, name="create")
     * @IsGranted("IS_AUTHENTICATED_ANONYMOUSLY")
     * @param UserRepository $userRepository
     * @param TokenRepository $tokenRepository
     * @return JsonResponse
     * @throws \Exception
     */
    public function create(UserRepository $userRepository,
                           TokenRepository $tokenRepository) {
        $dateTime = new \DateTime();
        $user = (new User())
            ->addRole(User::ROLE_UNREGISTRED_USER)
            ->setPermanent(false)
            ->setCreatedAt($dateTime)
            ->setUpdatedAt($dateTime);

        $user->setUsername(Uuid::uuid4());
        $token = new Token();
        $userRepository->create($user);

        $token->setUser($user)
            ->setCreatedAt($dateTime)
            ->setUpdatedAt($dateTime);
        $tokenRepository->create($token);

        return new JsonResponse($token->toArray());
    }

    /**
     * @Route("/user/register", methods={"POST"}, name="register")
     * @IsGranted("ROLE_UNREGISTRED_USER")
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
                             UserPasswordEncoderInterface $encoder) {

        $user = $this->getUser();
        if (!($user instanceof User)) {
            throw new ClassException($user, '$user',User::class);
        }
        $now = new \DateTime();

        $user->setUsername($request->get('username'))
            ->setPlainPassword($request->get('password'))
            ->removeRole(User::ROLE_UNREGISTRED_USER)
            ->addRole(User::ROLE_REGISTRED_USER)
            ->setPermanent(true)
            ->setUpdatedAt($now)
            ->setRegistredAt($now);
        $errors = $validator->validate($user);
        if (\count($errors) > 0) {
            throw new ValidationException('Ошибка данных', $errors);
        }

        $token = (new Token())
            ->setCreatedAt($now)
            ->setUpdatedAt($now)
            ->setUser($user);

        $encoded = $encoder->encodePassword($user, $user->getPlainPassword());
        $user->setPassword($encoded);
        $userRepository->update($user);
        $tokenRepository->create($token);
        $tokenRepository->deleteOld($now, $user);

        return new JsonResponse($token->toArray());
    }

    /**
     * @Route("/user/info", methods={"GET"})
     * @IsGranted("ROLE_USER")
     * @return JsonResponse
     * @throws ClassException
     */
    public function info()
    {
        $user = $this->getUser();
        if (!($user instanceof User)) {
            throw new ClassException($user, '$user',User::class);
        }
        return new JsonResponse($user->toArray());

    }
}
