<?php

declare(strict_types=1);

namespace Api\Security;

use Iam\Access\Application\Finder\Grant\GrantResult;
use Iam\Access\Application\Query\ListGrantsForIdentity\ListGrantsForIdentity;
use Iam\Identity\Application\Exception\ApiTokenCredentialAuthenticationFailedException;
use Iam\Identity\Application\Security\ApiTokenCredentialAuthenticatorInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
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
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class ApiTokenAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    private const string HEADER = 'X-Api-Key';

    public function __construct(
        private ApiTokenCredentialAuthenticatorInterface $authenticator,
        private QueryBusInterface $queryBus,
    ) {
    }

    public function supports(Request $request): bool
    {
        return $request->headers->has(self::HEADER);
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    public function authenticate(Request $request): Passport
    {
        $token = (string) $request->headers->get(self::HEADER);

        if (!str_contains($token, '.')) {
            throw new CustomUserMessageAuthenticationException('Malformed API key.');
        }

        [$identifier, $secret] = explode('.', $token, 2);

        try {
            $identityId = $this->authenticator->authenticate($identifier, $secret);
        } catch (ApiTokenCredentialAuthenticationFailedException) {
            throw new CustomUserMessageAuthenticationException('Invalid API key.');
        }

        $grants = $this->grantsFor($identityId);

        return new SelfValidatingPassport(
            new UserBadge($identityId, static fn (string $identifier): IamUser => new IamUser($identifier, $grants)),
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_UNAUTHORIZED);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new JsonResponse(['error' => 'An API key is required.'], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @return list<string>
     *
     * @throws ApplicationExceptionInterface
     */
    private function grantsFor(string $identityId): array
    {
        $grants = [];

        foreach ($this->queryBus->ask(new ListGrantsForIdentity($identityId)) as $grant) {
            \assert($grant instanceof GrantResult);
            $grants[] = $grant->permission;
        }

        return $grants;
    }
}
