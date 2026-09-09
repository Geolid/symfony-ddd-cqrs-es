<?php

declare(strict_types=1);

namespace Compliance\Erasing\Application\Command\RequestErasure;

use Compliance\Erasing\Application\Command\RequestErasure\Exception\ErasureAlreadyRequestedException;
use Compliance\Erasing\Domain\Erasure;
use Compliance\Erasing\Domain\Exception\ErasureAlreadyExistsException;
use Compliance\Erasing\Domain\Repository\ErasureRepositoryInterface;
use Compliance\Erasing\Domain\ValueObject\ErasureId;
use Compliance\Erasing\Domain\ValueObject\ErasureUniqueKey;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Uniqueness\Exception\UniqueValueAlreadyTakenException;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;

#[CommandHandler]
final readonly class RequestErasureHandler
{
    public function __construct(
        private ErasureRepositoryInterface $repository,
        private UniqueValueRegistryInterface $uniqueValues,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ErasureAlreadyRequestedException
     */
    public function __invoke(RequestErasure $command): void
    {
        $id = ErasureId::fromString($command->id);

        try {
            $this->uniqueValues->reserve(UniqueKey::for(ErasureUniqueKey::IDENTITY), $command->identityId, $id->toString());
        } catch (UniqueValueAlreadyTakenException $e) {
            throw ErasureAlreadyRequestedException::forIdentity($command->identityId, $e);
        }

        $erasure = Erasure::request($id, $command->identityId, $this->clock->now());

        try {
            $this->repository->save($erasure);
        } catch (ErasureAlreadyExistsException) {
            return;
        }
    }
}
