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
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;

    public function __construct(EntityManagerInterface $em, UserPasswordEncoderInterface $encoder)
    {
        $this->em = $em;
        $this->encoder = $encoder;
    }

    public function createPermanent(string $username, string $password)
    {

    }

    public function createTemporary(): Token
    {
        $dateTime = new \DateTime();
        $userRepository = $this->em->getRepository(User::class);
        $tokenRepository = $this->em->getRepository(Token::class);
        $user = (new User())
            ->setPermanent(false)
            ->setCreatedAt($dateTime)
            ->setUpdatedAt($dateTime);
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
        $token->setUser($user)
            ->setCreatedAt($dateTime)
            ->setUpdatedAt($dateTime);
        try {
            $tokenRepository->create($token);
        } catch (\Exception $e) {
            // TODO log;
            throw $e;
        }

        return $token;
    }

    public function register(User $user, string $username, string $plainPassword)
    {
        $dateTime = new \DateTime();
        $user->setUsername($username)
            ->setPermanent(true)
            ->addRole('ROLE_REGISTRATED_USER')
            ->setPlainPassword($plainPassword)
            ->setUpdatedAt($dateTime)
            ->setRegistratedAt($dateTime);
        $encoded = $this->encoder->encodePassword($user, $plainPassword);
        $user->setPassword($encoded);
        $userRepository = $this->em->getRepository(User::class);
        $userRepository->update($user);
    }
}