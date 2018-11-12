<?php

namespace App\Controller;

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
     * @Route("/user/temporary/create", methods={"POST"})
     * @param UserService $userService
     * @return JsonResponse
     * @throws \Exception
     */
    public function createTemporary(UserService $userService) {
        try {
            $token = $userService->createTemporaryUser();
            return new JsonResponse($token->toArray());
        } catch (\Exception $e) {
            // TODO log
            throw $e;
        }
    }
}
