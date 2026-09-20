<?php

declare(strict_types=1);

namespace Storefront\Security;

use Iam\Authentication\Application\CredentialVerification\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Authentication\Application\Finder\PasswordCredential\Exception\PasswordCredentialResultNotFoundException;
use Iam\Authentication\Application\Query\GetPasswordCredentialByIdentity\GetPasswordCredentialByIdentity;
use Iam\Identity\Application\Finder\Identity\IdentityResult;
use Iam\Identity\Application\Query\GetIdentityByEmail\GetIdentityByEmail;
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
        $identity = $this->identityOf($identifier);
        $passwordChangedAt = $this->passwordChangedAtOf($identity->id);

        return new PasswordUser($identity->id, $identifier, $identity->fullName, $identity->i, $passwordChangedAt);
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    public function refreshUser(UserInterface $user): PasswordUser
    {
        if (!$user instanceof PasswordUser) {
            throw new UnsupportedUserException(\sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $identity = $this->identityOf($user->getUserIdentifier());
        $passwordChangedAt = $this->passwordChangedAtOf($user->identityId());

        return new PasswordUser($user->identityId(), $user->getUserIdentifier(), $identity->fullName, true, $passwordChangedAt);
    }

    public function supportsClass(string $class): bool
    {
        return PasswordUser::class === $class;
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    private function identityOf(string $email): IdentityResult
    {
        $identity = $this->queryBus->ask(new GetIdentityByEmail($email));

        if (null === $identity) {
            throw new UserNotFoundException(\sprintf('No identity for email "%s".', $email));
        }

        return $identity;
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    private function passwordChangedAtOf(string $identityId): string
    {
        try {
            $credential = $this->queryBus->ask(new GetPasswordCredentialByIdentity($identityId));
        } catch (IdentityResultNotFoundException|IdentityNotAuthenticatableException $e) {
            throw new DisabledException(\sprintf('Identity "%s" is not authenticatable.', $identityId), $e->getCode(), previous: $e);
        } catch (PasswordCredentialResultNotFoundException $e) {
            throw new UserNotFoundException($e->getMessage(), $e->getCode(), previous: $e);
        }

        return $credential->passwordChangedAt->format(\DateTimeInterface::ATOM);
    }
}
