<?php
// src/Security/ApiKeyAuthenticator.php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use App\Repository\UserRepository;

class ApiKeyAuthenticator extends AbstractAuthenticator
{
    public function __construct(private UserRepository $userRepository) {}

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('X-API-KEY');
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $apiKey = $request->headers->get('X-API-KEY');

        return new SelfValidatingPassport(
            new UserBadge($apiKey, function($apiKey) {
                return $this->userRepository->findOneBy(['apiKey' => $apiKey]);
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): ?JsonResponse
    {
        return null; // continue the request
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        return new JsonResponse(['error' => 'Invalid API Key'], 401);
    }
}
