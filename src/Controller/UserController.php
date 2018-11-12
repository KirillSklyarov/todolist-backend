<?php

namespace App\Controller;

use App\Util\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class UserController
 * @package App\Controller
 * @Route("/api/v1")
 */
class UserController extends AbstractController
{
//    /**
//     * @Route("/user", name="user")
//     */
//    public function index()
//    {
//        return $this->json([
//            'message' => 'Welcome to your new controller!',
//            'path' => 'src/Controller/UserController.php',
//        ]);
//    }

    /**
     * @Route("/user/temporary/create", methods={"POST"})
     */
    public function createTemporary(UserService $userService) {
//        $userService = $this->get('user.service');
        $token = $userService->createTemporaryUser();
        dump($token);
        die;
    }
}
