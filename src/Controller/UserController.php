<?php

namespace App\Controller;

use App\Entity\Token;
use App\Entity\User;
use App\Util\UserService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
     * @param UserService $userService
     * @return JsonResponse
     * @throws \Exception
     */
    public function create(UserService $userService) {


        $dateTime = new \DateTime();
        $userRepository = $this->em->getRepository(User::class);
        $tokenRepository = $this->em->getRepository(Token::class);
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

        $token = $userService->createTemporary();
        return new JsonResponse($token->toArray());
    }

    /**
     * @Route("/user/register", methods={"POST"}, name="register")
     * @param UserService $userService
     * @return JsonResponse
     * @throws \Exception
     */
    public function register() {
        $now = new \DateTime();
        $user = $this->getUser();
        $user->clearTokens();
        $userRepository = $this->getDoctrine()->getRepository(User::class);
        $userRepository->update($user);
        $token = (new Token())
            ->setCreatedAt($now)
            ->setUpdatedAt($now)
            ->setUser($user);
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
