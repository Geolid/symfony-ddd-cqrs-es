<?php

declare(strict_types=1);

namespace Finance\Refund\Infrastructure\EventStore;

use Finance\Refund\Domain\Exception\RefundAlreadyExistsException;
use Finance\Refund\Domain\Exception\RefundNotFoundException;
use Finance\Refund\Domain\Refund;
use Finance\Refund\Domain\Repository\RefundRepositoryInterface;
use Finance\Refund\Domain\ValueObject\RefundId;
use Patchlevel\EventSourcing\Repository\AggregateAlreadyExists;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PatchlevelRefundRepository implements RefundRepositoryInterface
{
    /**
     * @param Repository<Refund> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.finance.refund.refund.repository')]
        private Repository $repository,
    ) {
    }

    public function has(RefundId $id): bool
    {
        return $this->repository->has($id);
    }

    public function load(RefundId $id): Refund
    {
        try {
            return $this->repository->load($id);
        } catch (AggregateNotFound) {
            throw RefundNotFoundException::forId($id->toString());
        }
    }

    public function save(Refund $refund): void
    {
        try {
            $this->repository->save($refund);
        } catch (AggregateAlreadyExists) {
            throw RefundAlreadyExistsException::forId($refund->id->toString());
        }
    }
}
