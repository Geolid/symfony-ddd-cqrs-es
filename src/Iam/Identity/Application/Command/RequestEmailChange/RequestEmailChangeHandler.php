<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\RequestEmailChange;

use Iam\Identity\Application\Command\RequestEmailChange\Exception\IdentityEmailAlreadyInUseException;
use Iam\Identity\Application\IdentityUniqueKey;
use Iam\Identity\Domain\Exception\EmailChangeRequestedTooRecentlyException;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\Exception\IdentityAlreadyExistsException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Iam\Identity\Domain\ValueObject\Email;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;

#[CommandHandler]
final readonly class RequestEmailChangeHandler
{
    public function __construct(
        private IdentityRepositoryInterface $repository,
        private UniquenessRegistryInterface $uniqueness,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws IdentityNotFoundException
     * @throws IdentityAlreadyErasedException
     * @throws EmailChangeRequestedTooRecentlyException
     * @throws IdentityEmailAlreadyInUseException
     * @throws IdentityAlreadyExistsException
     */
    public function __invoke(RequestEmailChange $command): void
    {
        $newEmail = Email::fromString($command->newEmail);

        if ($this->uniqueness->isClaimed(UniqueKey::for(IdentityUniqueKey::EMAIL), $newEmail->value, $command->id)) {
            throw IdentityEmailAlreadyInUseException::forEmail($newEmail->value);
        }

        $identity = $this->repository->load(IdentityId::fromString($command->id));
        $identity->requestEmailChange($newEmail, $this->clock->now());

        $this->repository->save($identity);
    }
}
