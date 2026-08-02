<?php

declare(strict_types=1);

namespace Api\Security;

use Iam\Identity\Application\Port\AuthenticateApiTokenCredentialInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final readonly class ApiTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(private AuthenticateApiTokenCredentialInterface $authenticator)
    {
    }

    public function supports(Request $request): ?bool
    {
        return str_starts_with((string) $request->headers->get('Authorization'), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $token = substr((string) $request->headers->get('Authorization'), 7);

        if (!str_contains($token, '.')) {
            throw new CustomUserMessageAuthenticationException('Malformed API token.');
        }

        [$identifier, $secret] = explode('.', $token, 2);

        $identityId = $this->authenticator->authenticate($identifier, $secret);

        if (null === $identityId) {
            throw new CustomUserMessageAuthenticationException('Invalid API token.');
        }

        return new SelfValidatingPassport(
            new UserBadge($identityId, static fn (string $identifier): IamUser => new IamUser($identifier)),
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_UNAUTHORIZED);
    }
}
