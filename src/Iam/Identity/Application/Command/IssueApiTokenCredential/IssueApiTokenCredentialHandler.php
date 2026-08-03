<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\IssueApiTokenCredential;

use Iam\Identity\Domain\ApiTokenCredential;
use Iam\Identity\Domain\Repository\ApiTokenCredentialRepositoryInterface;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Identity\Domain\ValueObject\ApiTokenCredentialId;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;

#[AsCommandHandler]
final readonly class IssueApiTokenCredentialHandler
{
    public function __construct(
        private ApiTokenCredentialRepositoryInterface $repository,
        private SecretHasherInterface $hasher,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(IssueApiTokenCredential $command): void
    {
        $this->repository->save(ApiTokenCredential::issue(
            ApiTokenCredentialId::fromString($command->id),
            IdentityId::fromString($command->identityId),
            $command->identifier,
            $command->secret,
            $this->hasher,
            $this->clock->now(),
            new \DateTimeImmutable($command->expiresAt),
        ));
    }
}
