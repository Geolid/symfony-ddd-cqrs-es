<?php

declare(strict_types=1);

namespace Storefront\Security;

use Iam\Authentication\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Authentication\Application\Finder\PasswordCredential\Exception\PasswordCredentialResultNotFoundException;
use Iam\Authentication\Application\Query\GetPasswordCredentialByEmail\GetPasswordCredentialByEmail;
use Iam\Authentication\Application\Query\GetPasswordCredentialByEmail\IdentityCredentialResult;
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
        return $this->toPasswordUser($this->credentialOf($identifier));
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    public function refreshUser(UserInterface $user): PasswordUser
    {
        if (!$user instanceof PasswordUser) {
            throw new UnsupportedUserException(\sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $credential = $this->credentialOf($user->getUserIdentifier());

        if (!$credential->identityAuthenticatable) {
            throw new DisabledException(\sprintf('Identity "%s" is not authenticatable.', $credential->identityId));
        }

        return $this->toPasswordUser($credential);
    }

    public function supportsClass(string $class): bool
    {
        return PasswordUser::class === $class;
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    private function credentialOf(string $email): IdentityCredentialResult
    {
        try {
            return $this->queryBus->ask(new GetPasswordCredentialByEmail($email));
        } catch (IdentityResultNotFoundException|PasswordCredentialResultNotFoundException $e) {
            throw new UserNotFoundException($e->getMessage(), $e->getCode(), previous: $e);
        }
    }

    private function toPasswordUser(IdentityCredentialResult $credential): PasswordUser
    {
        return new PasswordUser($credential->identityId, $credential->email, $credential->identityAuthenticatable, $credential->passwordChangedAt->format(\DateTimeInterface::ATOM));
    }
}
