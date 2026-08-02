<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\IssueApiToken;

use Iam\Identity\Domain\ApiToken;
use Iam\Identity\Domain\ApiTokenId;
use Iam\Identity\Domain\IdentityId;
use Iam\Identity\Domain\Repository\ApiTokenRepositoryInterface;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;

#[AsCommandHandler]
final readonly class IssueApiTokenHandler
{
    public function __construct(
        private ApiTokenRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(IssueApiToken $command): void
    {
        $this->repository->save(ApiToken::issue(
            ApiTokenId::fromString($command->id),
            IdentityId::fromString($command->identityId),
            $command->identifier,
            $command->secretHash,
            $this->clock->now(),
        ));
    }
}
