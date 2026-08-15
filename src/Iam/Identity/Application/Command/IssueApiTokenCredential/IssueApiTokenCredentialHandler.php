<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\IssueApiTokenCredential;

use Iam\Identity\Application\Exception\LabelAlreadyTakenException;
use Iam\Identity\Domain\ApiTokenCredential;
use Iam\Identity\Domain\Exception\IdentityNotActiveException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\Repository\ApiTokenCredentialRepositoryInterface;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
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
        private IdentityRepositoryInterface $identities,
        private ApiTokenCredentialRepositoryInterface $repository,
        private UniqueValueRegistryInterface $uniqueValues,
        private SecretHasherInterface $hasher,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws IdentityNotFoundException
     * @throws IdentityNotActiveException
     * @throws LabelAlreadyTakenException
     */
    public function __invoke(IssueApiTokenCredential $command): void
    {
        $identityId = IdentityId::fromString($command->identityId);
        $this->identities->load($identityId)->ensureActive();

        $label = Label::fromString($command->label);
        $fingerprint = $label->fingerprintFor($command->identityId);

        try {
            $this->uniqueValues->reserve(ApiTokenCredentialUniqueValue::LABEL, $fingerprint);
        } catch (UniqueValueAlreadyTakenException $e) {
            throw LabelAlreadyTakenException::forFingerprint($fingerprint, $e);
        }

        $credential = ApiTokenCredential::issue(
            id: ApiTokenCredentialId::fromString($command->id),
            identityId: $identityId,
            identifier: $command->identifier,
            label: $label,
            plainSecret: $command->secret,
            hasher: $this->hasher,
            issuedAt: $this->clock->now(),
            expiresAt: new \DateTimeImmutable($command->expiresAt),
        );

        $this->repository->save($credential);
    }
}
