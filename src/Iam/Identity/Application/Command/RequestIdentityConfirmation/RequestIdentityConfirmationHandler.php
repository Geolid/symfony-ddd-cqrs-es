<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\RequestIdentityConfirmation;

use Iam\Identity\Domain\Exception\ConfirmationRequestedTooRecentlyException;
use Iam\Identity\Domain\Exception\IdentityAlreadyConfirmedException;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\Exception\IdentityAlreadyExistsException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class RequestIdentityConfirmationHandler
{
    public function __construct(
        private IdentityRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws IdentityNotFoundException
     * @throws IdentityAlreadyErasedException
     * @throws IdentityAlreadyConfirmedException
     * @throws ConfirmationRequestedTooRecentlyException
     * @throws IdentityAlreadyExistsException
     */
    public function __invoke(RequestIdentityConfirmation $command): void
    {
        $identity = $this->repository->load(IdentityId::fromString($command->id));
        $identity->requestConfirmation($this->clock->now());

        $this->repository->save($identity);
    }
}
