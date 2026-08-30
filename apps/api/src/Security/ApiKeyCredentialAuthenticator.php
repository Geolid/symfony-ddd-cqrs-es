<?php

declare(strict_types=1);

namespace Api\Security;

use Iam\Authentication\Application\ApiKey\ApiKeyCredentialVerifierInterface;
use Iam\Authentication\Application\Exception\ApiKeyCredentialResultNotFoundException;
use Iam\Authentication\Application\Exception\ApiKeyCredentialRevokedException;
use Iam\Authentication\Application\Exception\IdentityNotAuthenticatableException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class ApiKeyCredentialAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    private const string HEADER = 'X-Api-Key';

    public function __construct(private readonly ApiKeyCredentialVerifierInterface $verifier)
    {
    }

    public function supports(Request $request): bool
    {
        return $request->headers->has(self::HEADER);
    }

    public function authenticate(Request $request): Passport
    {
        $apiKey = (string) $request->headers->get(self::HEADER);

        if (!str_contains($apiKey, '.')) {
            throw new CustomUserMessageAuthenticationException('Malformed API key.');
        }

        [$keyId, $secret] = explode('.', $apiKey, 2);

        return new Passport(
            new UserBadge($keyId),
            new CustomCredentials(
                function (mixed $secret, UserInterface $user): bool {
                    \assert(\is_string($secret));
                    \assert($user instanceof ApiUser);

                    try {
                        return $this->verifier->verify($user->getUserIdentifier(), $secret);
                    } catch (ApiKeyCredentialResultNotFoundException|ApiKeyCredentialRevokedException|IdentityNotAuthenticatableException) {
                        return false;
                    }
                },
                $secret,
            ),
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        return new JsonResponse(['error' => 'Invalid API key.'], Response::HTTP_UNAUTHORIZED);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): JsonResponse
    {
        return new JsonResponse(['error' => 'An API key is required.'], Response::HTTP_UNAUTHORIZED);
    }
}
