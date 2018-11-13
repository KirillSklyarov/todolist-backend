<?php

namespace App\Controller;

use App\Entity\Token;
use App\Entity\User;
use App\Util\UserService;
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
    /**
     * @Route("/user/create", methods={"POST"}, name="create")
     * @param UserService $userService
     * @return JsonResponse
     * @throws \Exception
     */
    public function create(UserService $userService) {
        try {
            $token = $userService->createTemporary();
            return new JsonResponse($token->toArray());
        } catch (\Exception $e) {
            // TODO log
            throw $e;
        }
    }

    /**
     * @Route("/user/register", methods={"POST"}, name="register")
     * @param UserService $userService
     * @return JsonResponse
     * @throws \Exception
     */
    public function register(UserService $userService) {
        $user = $this->getUser();
//        $token = $this->container->get('security.token_storage')->getToken();
        $user->clearTokens();
        $userRepository = $this->getDoctrine()->getRepository(User::class);
        $userRepository->update($user);
        $token = (new Token())
            ->setUser($user);
        die;

        try {
            $token = $userService->createTemporary();
            return new JsonResponse($token->toArray());
        } catch (\Exception $e) {
            // TODO log
            throw $e;
        }
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
