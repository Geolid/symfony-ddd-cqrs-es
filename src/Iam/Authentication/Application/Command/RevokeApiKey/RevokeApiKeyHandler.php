<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\RevokeApiKey;

use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialAlreadyExistsException;
use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialNotFoundException;
use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialOwnedByAnotherIdentityException;
use Iam\Authentication\Domain\ApiKeyCredential\Repository\ApiKeyCredentialRepositoryInterface;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialId;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialUniqueKey;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;

#[CommandHandler]
final readonly class RevokeApiKeyHandler
{
    public function __construct(
        private ApiKeyCredentialRepositoryInterface $repository,
        private UniqueValueRegistryInterface $uniqueValues,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ApiKeyCredentialNotFoundException
     * @throws ApiKeyCredentialOwnedByAnotherIdentityException
     * @throws ApiKeyCredentialAlreadyExistsException
     */
    public function __invoke(RevokeApiKey $command): void
    {
        $credential = $this->repository->load(ApiKeyCredentialId::fromString($command->id));
        $credential->revoke($command->identityId, $this->clock->now());

        $this->repository->save($credential);

        $this->uniqueValues->release(
            UniqueKey::for(ApiKeyCredentialUniqueKey::LABEL, $command->identityId),
            $credential->label->value,
            $credential->id->toString(),
        );
    }
}
