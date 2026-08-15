<?php

declare(strict_types=1);

namespace Api\Security;

use Iam\Access\Application\Finder\Grant\GrantResult;
use Iam\Access\Application\Query\ListGrantsForIdentity\ListGrantsForIdentity;
use Iam\Identity\Application\Query\GetApiTokenCredentialByIdentifier\GetApiTokenCredentialByIdentifier;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Exception\ResultNotFoundException;
use Shared\Application\Query\QueryBusInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<ApiUser>
 */
final readonly class ApiUserProvider implements UserProviderInterface
{
    public function __construct(private QueryBusInterface $queryBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    public function loadUserByIdentifier(string $identifier): ApiUser
    {
        try {
            $credential = $this->queryBus->ask(new GetApiTokenCredentialByIdentifier($identifier));
        } catch (ResultNotFoundException $e) {
            throw new UserNotFoundException($e->getMessage(), 0, $e);
        }

        return new ApiUser(
            $credential->id,
            $credential->identityId,
            $credential->identifier,
            $this->grantsFor($credential->identityId),
            $credential->revoked,
            $credential->expiresAt,
            $credential->identityStatus,
        );
    }

    public function refreshUser(UserInterface $user): ApiUser
    {
        // Stateless firewall: never actually called, kept minimal to satisfy the interface contract.
        if (!$user instanceof ApiUser) {
            throw new UnsupportedUserException(\sprintf('Instances of "%s" are not supported.', $user::class));
        }

        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return ApiUser::class === $class;
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
