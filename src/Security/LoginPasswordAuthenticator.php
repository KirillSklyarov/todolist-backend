<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 15.11.2018
 * Time: 21:39
 */

namespace App\Security;


use App\Entity\Token;
use App\Entity\User;
use App\Util\JsonConverter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class LoginPasswordAuthenticator extends AbstractAuthenticator
{
    use JsonConverter;

    /**
     * @var string
     */
    protected $message = 'Wrong username or password';

    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;

    public function __construct(EntityManagerInterface $em,
                                UserPasswordEncoderInterface $encoder)
    {
        parent::__construct($em);
        $this->encoder = $encoder;
    }

    public function supports(Request $request)
    {
//        die;
        return $request->getRequestUri() === AbstractAuthenticator::LOGIN_URI;
    }

    /**
     * Called on every request. Return whatever credentials you want to
     * be passed to getUser() as $credentials.
     */
    public function getCredentials(Request $request)
    {
        if ($request->getContentType() !== 'json' || !$request->getContent()) {
            throw new BadRequestHttpException(
                'Invalid json body: ______' . \json_last_error_msg(),
                null,
                \json_last_error()
            );
        }

        return $this->convert($request->getContent());
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $username = \array_key_exists('username', $credentials) ?
            $credentials['username'] : null;
        $password = \array_key_exists('password', $credentials) ?
            $credentials['password'] : null;
        if (!$username || !$password) {
            $message = 'Bad request.';
            if (!$username) {
                $message .= ' Field "username" is required.';
            }
            if (!$password) {
                $message .= ' Field "password" is required.';
            }

            throw new BadRequestHttpException($message);
        }

        $user = $this->em->getRepository(User::class)
            ->findOneBy(['username' => $username]);

        if (null === $user) {
            return;
        }
        if (!$this->encoder->isPasswordValid($user, $password)) {
            return;
        }
        $token = new Token();
        $now = new \DateTime();
        $token->setUser($user)
            ->setCreatedAt($now)
            ->setUpdatedAt($now);
        $tokenRepository = $this->em->getRepository(Token::class);
        $tokenRepository->create($token);
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
        $data = array(
            'message' => $this->message,
            'code' => $this->code
        );

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Called when authentication is needed, but it's not sent
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $data = array(
            'message' => 'Authentication Required'
        );

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }
}