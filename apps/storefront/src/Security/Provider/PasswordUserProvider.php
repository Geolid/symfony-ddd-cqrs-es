<?php

declare(strict_types=1);

namespace Storefront\Security\Provider;

use Iam\Authentication\Application\Query\GetDeviceTrustByIdentity\GetDeviceTrustByIdentity;
use Iam\Authentication\Application\Query\GetPasswordCredentialByIdentity\GetPasswordCredentialByIdentity;
use Iam\Identity\Application\Query\GetIdentityByEmail\GetIdentityByEmail;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Storefront\Security\PasswordUser;
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
        $identity = $this->queryBus->ask(new GetIdentityByEmail($identifier));

        if (null === $identity) {
            throw new UserNotFoundException(\sprintf('No identity for email "%s".', $identifier));
        }

        $credential = $this->queryBus->ask(new GetPasswordCredentialByIdentity($identity->id));

        if (null === $credential) {
            throw new UserNotFoundException(\sprintf('No password credential for identity "%s".', $identity->id));
        }

        $deviceTrust = $this->queryBus->ask(new GetDeviceTrustByIdentity($identity->id));

        return new PasswordUser($identity->id, $identifier, $identity->fullName, $identity->verificationStatus, $identity->moderationStatus, $credential->changedAt, $deviceTrust?->revokedAt);
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws UnsupportedUserException
     */
    public function refreshUser(UserInterface $user): PasswordUser
    {
        if (!$user instanceof PasswordUser) {
            throw new UnsupportedUserException(\sprintf('Instances of "%s" are not supported.', $user::class));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return PasswordUser::class === $class;
    }
}
