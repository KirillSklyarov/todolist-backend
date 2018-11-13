<?php

namespace App\Controller;

use App\Entity\Token;
use App\Entity\User;
use App\Repository\TokenRepository;
use App\Repository\UserRepository;
use App\Util\UserService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class UserController
 * @package App\Controller
 * @Route("/api/v1")
 */
class UserController extends AbstractController
{

    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @Route("/user/create", methods={"POST"}, name="create")
     * @param UserRepository $userRepository
     * @param TokenRepository $tokenRepository
     * @return JsonResponse
     * @throws \Exception
     */
    public function create(UserRepository $userRepository, TokenRepository $tokenRepository) {
        $dateTime = new \DateTime();
        $user = (new User())
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
     * @param Request $request
     * @param UserRepository $userRepository
     * @param TokenRepository $tokenRepository
     * @return JsonResponse
     * @throws \Exception
     */
    public function register(Request $request,
                             UserRepository $userRepository,
                             TokenRepository $tokenRepository) {
        $errors = [];
        $username = $request->get('password');
        $password = $request->get('username');
        $user = $this->getUser();
        $now = new \DateTime();

        $token = (new Token())
            ->setCreatedAt($now)
            ->setUpdatedAt($now)
            ->setUser($user);
        $user->clearTokens();
        $userRepository->update($user);

        $tokenRepository->create($token);
        return new JsonResponse($token->toArray());
    }

    /**
     * @Route("/user/info", methods={"GET"})
     */
    public function info()
    {
        $user = $this->getUser();
        return new JsonResponse([
           'hello' => 'world',
            'user' => $this->getUser()->getUsername()
        ]);

    }
}
