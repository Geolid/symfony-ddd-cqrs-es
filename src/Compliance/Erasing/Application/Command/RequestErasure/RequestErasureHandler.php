<?php

declare(strict_types=1);

namespace Compliance\Erasing\Application\Command\RequestErasure;

use Compliance\Erasing\Application\Command\RequestErasure\Exception\ErasureAlreadyClaimedException;
use Compliance\Erasing\Application\Uniqueness\ErasureUniqueKey;
use Compliance\Erasing\Domain\Erasure;
use Compliance\Erasing\Domain\Exception\ErasureAlreadyExistsException;
use Compliance\Erasing\Domain\Repository\ErasureRepositoryInterface;
use Compliance\Erasing\Domain\ValueObject\ErasureId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Uniqueness\Exception\UniquenessViolatedException;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;

#[CommandHandler]
final readonly class RequestErasureHandler
{
    public function __construct(
        private ErasureRepositoryInterface $repository,
        private UniquenessRegistryInterface $uniqueValues,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ErasureAlreadyClaimedException
     * @throws ErasureAlreadyExistsException
     */
    public function __invoke(RequestErasure $command): void
    {
        $id = ErasureId::fromString($command->id);

        try {
            $this->uniqueValues->claim(UniqueKey::for(ErasureUniqueKey::IDENTITY), $command->identityId, $id->toString());
        } catch (UniquenessViolatedException $e) {
            throw ErasureAlreadyClaimedException::forIdentity($command->identityId, $e);
        }

        $erasure = Erasure::request($id, $command->identityId, $this->clock->now());

        $this->repository->save($erasure);
    }
}
