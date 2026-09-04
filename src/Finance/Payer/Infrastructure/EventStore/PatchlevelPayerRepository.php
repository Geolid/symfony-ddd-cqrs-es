<?php

declare(strict_types=1);

namespace Finance\Payer\Infrastructure\EventStore;

use Finance\Payer\Domain\Exception\PayerAlreadyExistsException;
use Finance\Payer\Domain\Exception\PayerNotFoundException;
use Finance\Payer\Domain\Payer;
use Finance\Payer\Domain\Repository\PayerRepositoryInterface;
use Finance\Payer\Domain\ValueObject\PayerId;
use Patchlevel\EventSourcing\Repository\AggregateAlreadyExists;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PatchlevelPayerRepository implements PayerRepositoryInterface
{
    /**
     * @param Repository<Payer> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.finance.payer.payer.repository')]
        private Repository $repository,
    ) {
    }

    public function has(PayerId $id): bool
    {
        return $this->repository->has($id);
    }

    public function load(PayerId $id): Payer
    {
        try {
            return $this->repository->load($id);
        } catch (AggregateNotFound) {
            throw PayerNotFoundException::forId($id->toString());
        }
    }

    public function save(Payer $payer): void
    {
        try {
            $this->repository->save($payer);
        } catch (AggregateAlreadyExists) {
            throw PayerAlreadyExistsException::forId($payer->id->toString());
        }
    }
}
