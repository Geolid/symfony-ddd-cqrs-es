<?php

declare(strict_types=1);

namespace Web\Security;

use Iam\Authentication\Application\Exception\PasswordCredentialResultNotFoundException;
use Iam\Authentication\Application\Query\GetPasswordCredentialByLogin\GetPasswordCredentialByLogin;
use Shared\Application\Exception\ApplicationExceptionInterface;
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
        } catch (PasswordCredentialResultNotFoundException $e) {
            throw new UserNotFoundException($e->getMessage(), $e->getCode(), previous: $e);
        }

        return new PasswordUser($credential->identityId, $credential->login, $credential->identityAuthenticatable, $credential->passwordChangedAt->format('c'));
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    public function refreshUser(UserInterface $user): PasswordUser
    {
        if (!$user instanceof PasswordUser) {
            throw new UnsupportedUserException(\sprintf('Instances of "%s" are not supported.', $user::class));
        }

        try {
            $credential = $this->queryBus->ask(new GetPasswordCredentialByLogin($user->getUserIdentifier()));
        } catch (PasswordCredentialResultNotFoundException $e) {
            throw new UserNotFoundException($e->getMessage(), $e->getCode(), previous: $e);
        }

        if (!$credential->identityAuthenticatable) {
            throw new DisabledException(\sprintf('Identity "%s" is not authenticatable.', $credential->identityId));
        }

        return new PasswordUser($credential->identityId, $credential->login, $credential->identityAuthenticatable, $credential->passwordChangedAt->format('c'));
    }

    public function supportsClass(string $class): bool
    {
        return PasswordUser::class === $class;
    }
}
