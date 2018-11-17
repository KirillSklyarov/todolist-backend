<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 12.11.2018
 * Time: 22:31
 */

namespace App\Security;

use App\Entity\Token;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class TokenAuthenticator extends AbstractGuardAuthenticator
{
    const AUTH_NO_NEED = [
        'user_create',
        'user_login'
    ];

    const TOKEN_LIFITIME = 'P30D';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var ParameterBagInterface
     */
    private $bag;

    /**
     * @var string
     */
    private $message = 'Authorisation required QQQ';

    /**
     * @var int
     */
    private $code = 1;

    public function __construct(ParameterBagInterface $bag,
                                EntityManagerInterface $em
                                )
    {
        $this->em = $em;
        $this->bag = $bag;
    }

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning false will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request)
    {
        return !\in_array($request->get('_route'),
            $this->bag->get('auth.no.need.routes'));
    }

    /**
     * Called on every request. Return whatever credentials you want to
     * be passed to getUser() as $credentials.
     */
    public function getCredentials(Request $request)
    {
        return [
            'token' => $request->headers->get('X-AUTH-TOKEN'),
        ];
    }

    /**
     * @param mixed $credentials
     * @param UserProviderInterface $userProvider
     * @return User|null|UserInterface
     * @throws \Exception
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $userToken = $credentials['token'];
        if (null === $userToken) {
            $this->message = 'Token is requered';
            return null;
        }

        $token = $this->em->getRepository(Token::class)
            ->findOneBy(['uuid' => $userToken]);
        if (null === $token) {
            $this->message = 'Token not found';
            return null;
        }
        $expiredAt = clone $token->getCreatedAt();
        $interval = new \DateInterval($this->bag->get('token.lifetime'));
        $expiredAt->add($interval);
        $now = new \DateTime();
        if ($expiredAt <= $now) {
            $this->message = 'Token is expired';
            return null;
        }
        $user = $token->getUser();
        $user->setCurrentToken($token);
        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        // check credentials - e.g. make sure the password is valid
        // no credential check is needed in this case

        // return true to cause authentication success
        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // on success, let the request continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $data = [
            'message' => $this->message,
            'code' => $this->code
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Called when authentication is needed, but it's not sent
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $data = [
            'message' => 'Authentication Required!!!!!!'
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }


    public function supportsRememberMe()
    {
        return false;
    }
}