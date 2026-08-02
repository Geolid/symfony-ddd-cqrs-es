<?php

declare(strict_types=1);

namespace Web\Security;

use Iam\Identity\Application\Exception\IdentityResultNotFoundException;
use Iam\Identity\Application\Language\PublishedIdentityStatus;
use Iam\Identity\Application\Query\GetIdentity\GetIdentity;
use Shared\Application\Query\QueryBusInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final readonly class IamUserProvider implements UserProviderInterface
{
    public function __construct(private QueryBusInterface $queryBus)
    {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        try {
            $identity = $this->queryBus->ask(new GetIdentity($identifier));
        } catch (IdentityResultNotFoundException $e) {
            throw new UserNotFoundException($e->getMessage(), 0, $e);
        }

        if (PublishedIdentityStatus::ACTIVE !== PublishedIdentityStatus::from($identity->status)) {
            throw new UserNotFoundException(\sprintf('Identity "%s" is not active.', $identifier));
        }

        return new IamUser($identifier);
    }

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
}
