<?php

declare(strict_types=1);

namespace Web\Security;

use Iam\Access\Application\Finder\Grant\GrantResult;
use Iam\Access\Application\Query\ListGrantsForIdentity\ListGrantsForIdentity;
use Iam\Identity\Application\Query\GetPasswordCredentialByLogin\GetPasswordCredentialByLogin;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Exception\ResultNotFoundException;
use Shared\Application\Query\QueryBusInterface;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<PasswordUser>
 */
final readonly class PasswordUserProvider implements UserProviderInterface
{
    public function __construct(private QueryBusInterface $queryBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    public function loadUserByIdentifier(string $identifier): PasswordUser
    {
        try {
            $credential = $this->queryBus->ask(new GetPasswordCredentialByLogin($identifier));
        } catch (ResultNotFoundException $e) {
            throw new UserNotFoundException($e->getMessage(), 0, $e);
        }

        return new PasswordUser(
            $credential->identityId,
            $credential->login,
            $this->grantsFor($credential->identityId),
            $credential->identityStatus,
        );
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    public function refreshUser(UserInterface $user): PasswordUser
    {
        if (!$user instanceof PasswordUser) {
            throw new UnsupportedUserException(\sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $refreshed = $this->loadUserByIdentifier($user->getUserIdentifier());

        if (!$refreshed->identityStatus->isActive()) {
            throw new DisabledException(\sprintf('Identity "%s" is not active.', $refreshed->identityId()));
        }

        return $refreshed;
    }

    public function supportsClass(string $class): bool
    {
        return PasswordUser::class === $class;
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
