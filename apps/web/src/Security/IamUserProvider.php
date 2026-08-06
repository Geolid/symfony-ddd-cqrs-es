<?php

declare(strict_types=1);

namespace Web\Security;

use Iam\Access\Application\Finder\Grant\GrantResult;
use Iam\Access\Application\Query\ListGrantsForIdentity\ListGrantsForIdentity;
use Iam\Identity\Application\Exception\IdentityResultNotFoundException;
use Iam\Identity\Application\Query\GetIdentity\GetIdentity;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<IamUser>
 */
final readonly class IamUserProvider implements UserProviderInterface
{
    public function __construct(private QueryBusInterface $queryBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        try {
            $identity = $this->queryBus->ask(new GetIdentity($identifier));
        } catch (IdentityResultNotFoundException $e) {
            throw new UserNotFoundException($e->getMessage(), 0, $e);
        }

        if (!$identity->status->isActive()) {
            throw new UserNotFoundException(\sprintf('Identity "%s" is not active.', $identifier));
        }

        return new IamUser($identifier, $this->grantsFor($identifier));
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof IamUser) {
            throw new UnsupportedUserException(\sprintf('Instances of "%s" are not supported.', $user::class));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return IamUser::class === $class;
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
