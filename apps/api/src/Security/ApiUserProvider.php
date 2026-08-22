<?php

declare(strict_types=1);

namespace Api\Security;

use Iam\Authentication\Application\Exception\ApiKeyCredentialResultNotFoundException;
use Iam\Authentication\Application\Query\GetApiKeyCredentialByKeyId\GetApiKeyCredentialByKeyId;
use Shared\Application\Exception\ApplicationExceptionInterface;
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
            $credential = $this->queryBus->ask(new GetApiKeyCredentialByKeyId($identifier));
        } catch (ApiKeyCredentialResultNotFoundException $e) {
            throw new UserNotFoundException($e->getMessage(), $e->getCode(), previous: $e);
        }

        return new ApiUser($credential->id, $credential->identityId, $credential->keyId);
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
}
