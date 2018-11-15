<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 15.11.2018
 * Time: 21:18
 */

namespace App\Security;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

abstract class AbstractAuthenticator extends AbstractGuardAuthenticator
{
    const LOGIN_URI = '/api/v1/user/login';
    const CREATE_USER = '/api/v1/user/create';

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var string
     */
    protected $message = 'Authorisation required QQQ';

    /**
     * @var int
     */
    protected $code = 1;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function supportsRememberMe()
    {
        return false;
    }
}