<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\IssueApiTokenCredential;

use Iam\Identity\Application\Exception\LabelAlreadyTakenException;
use Iam\Identity\Domain\ApiTokenCredential;
use Iam\Identity\Domain\Repository\ApiTokenCredentialRepositoryInterface;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Identity\Domain\ValueObject\ApiTokenCredentialId;
use Iam\Identity\Domain\ValueObject\ApiTokenCredentialUniqueValue;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\Label;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;
use Shared\Domain\Exception\UniqueValueAlreadyTakenException;
use Shared\Domain\Service\UniqueValueRegistryInterface;

#[AsCommandHandler]
final readonly class IssueApiTokenCredentialHandler
{
    public function __construct(
        private ApiTokenCredentialRepositoryInterface $repository,
        private UniqueValueRegistryInterface $uniqueValues,
        private SecretHasherInterface $hasher,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws LabelAlreadyTakenException
     */
    public function __invoke(IssueApiTokenCredential $command): void
    {
        $label = Label::fromString($command->label);
        $fingerprint = $label->fingerprintFor($command->identityId);

        try {
            $this->uniqueValues->reserve(ApiTokenCredentialUniqueValue::LABEL, $fingerprint);
        } catch (UniqueValueAlreadyTakenException) {
            throw LabelAlreadyTakenException::forFingerprint($fingerprint);
        }

        $this->repository->save(ApiTokenCredential::issue(
            ApiTokenCredentialId::fromString($command->id),
            IdentityId::fromString($command->identityId),
            $command->identifier,
            $label,
            $command->secret,
            $this->hasher,
            $this->clock->now(),
            new \DateTimeImmutable($command->expiresAt),
        ));
    }
}
