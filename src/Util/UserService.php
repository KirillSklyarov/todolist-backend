<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 09.11.2018
 * Time: 1:55
 */

namespace App\Util;


use App\Entity\Token;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class UserService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var PasswordEncoderInterface
     */
    private $encoder;

    public function __construct(EntityManagerInterface $em, PasswordEncoderInterface $encoder)
    {
        $this->em = $em;
        $this->encoder = $encoder;
    }

    public function createPermanent(string $username, string $password)
    {

    }

    public function createTemporary(): Token
    {
        $userRepository = $this->em->getRepository(User::class);
        $tokenRepository = $this->em->getRepository(Token::class);
        $user = (new User())
            ->setPermanent(false);
        try {
            $user->setUsername(Uuid::uuid4());
            $token = new Token();
        } catch (\Exception $e) {
            // TODO log;
            throw $e;
        }
        try {
            $userRepository->create($user);
        } catch (\Exception $e) {
            // TODO log;
            throw $e;
        }
        $token->setUser($user);
        try {
            $tokenRepository->create($token);
        } catch (\Exception $e) {
            // TODO log;
            throw $e;
        }

        return $token;
    }

    public function convert(User $user, string $username, string $plainPassword)
    {
//        $user->set
    }
}