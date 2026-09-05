<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\IssueApiKeyCredential;

use Iam\Authentication\Application\ApiKey\Exception\ApiKeyCredentialLabelAlreadyTakenException;
use Iam\Authentication\Domain\ApiKeyCredential\ApiKeyCredential;
use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialAlreadyExistsException;
use Iam\Authentication\Domain\ApiKeyCredential\Repository\ApiKeyCredentialRepositoryInterface;
use Iam\Authentication\Domain\ApiKeyCredential\Service\ApiKeyHasherInterface;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialId;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialUniqueKey;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\KeyId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Uniqueness\Exception\UniqueValueAlreadyTakenException;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\Label;

#[CommandHandler]
final readonly class IssueApiKeyCredentialHandler
{
    public function __construct(
        private ApiKeyCredentialRepositoryInterface $repository,
        private UniqueValueRegistryInterface $uniqueValues,
        private ApiKeyHasherInterface $hasher,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ApiKeyCredentialLabelAlreadyTakenException
     * @throws ApiKeyCredentialAlreadyExistsException
     */
    public function __invoke(IssueApiKeyCredential $command): void
    {
        $id = ApiKeyCredentialId::fromString($command->id);
        $label = Label::fromString($command->label);

        try {
            $this->uniqueValues->reserve(UniqueKey::for(ApiKeyCredentialUniqueKey::LABEL, $command->identityId), $label->value, $id->toString());
        } catch (UniqueValueAlreadyTakenException $e) {
            throw ApiKeyCredentialLabelAlreadyTakenException::forLabel($label->value, $e);
        }

        $credential = ApiKeyCredential::issue(
            id: $id,
            identityId: $command->identityId,
            label: $label,
            keyId: KeyId::fromString($command->keyId),
            secret: $command->secret,
            hasher: $this->hasher,
            issuedAt: $this->clock->now(),
        );

        $this->repository->save($credential);
    }
}
