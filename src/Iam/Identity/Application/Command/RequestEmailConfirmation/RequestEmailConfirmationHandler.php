<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\RequestEmailConfirmation;

use Iam\Identity\Domain\Exception\EmailConfirmationRequestedTooRecentlyException;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\Exception\IdentityAlreadyExistsException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\Exception\IdentityNotPendingException;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class RequestEmailConfirmationHandler
{
    public function __construct(
        private IdentityRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws IdentityNotFoundException
     * @throws IdentityAlreadyErasedException
     * @throws IdentityNotPendingException
     * @throws EmailConfirmationRequestedTooRecentlyException
     * @throws IdentityAlreadyExistsException
     */
    public function __invoke(RequestEmailConfirmation $command): void
    {
        $identity = $this->repository->load(IdentityId::fromString($command->id));
        $identity->requestEmailConfirmation($this->clock->now());

        $this->repository->save($identity);
    }
}
