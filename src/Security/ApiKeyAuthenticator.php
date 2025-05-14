<?php
namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiKeyAuthenticator extends AbstractAuthenticator
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function supports(Request $request): ?bool
    {
        // Supporte les requêtes avec un header 'X-API-KEY'
        return $request->headers->has('X-API-KEY');
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $apiKey = $request->headers->get('X-API-KEY');

        if (!$apiKey) {
            throw new CustomUserMessageAuthenticationException('No API key provided');
        }

        return new SelfValidatingPassport(
            new UserBadge($apiKey, function (string $apiKey): UserInterface {
                $user = $this->userRepository->findOneBy(['apiKey' => $apiKey]);

                if (!$user) {
                    throw new CustomUserMessageAuthenticationException('Invalid API Key');
                }

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // API : aucune redirection
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new Response(json_encode([
            'message' => $exception->getMessageKey()
        ]), Response::HTTP_UNAUTHORIZED, ['Content-Type' => 'application/json']);
    }
}
