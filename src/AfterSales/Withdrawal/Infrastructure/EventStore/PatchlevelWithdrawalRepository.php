<?php

declare(strict_types=1);

namespace AfterSales\Withdrawal\Infrastructure\EventStore;

use AfterSales\Withdrawal\Domain\Exception\WithdrawalAlreadyExistsException;
use AfterSales\Withdrawal\Domain\Exception\WithdrawalNotFoundException;
use AfterSales\Withdrawal\Domain\Repository\WithdrawalRepositoryInterface;
use AfterSales\Withdrawal\Domain\ValueObject\WithdrawalId;
use AfterSales\Withdrawal\Domain\Withdrawal;
use Patchlevel\EventSourcing\Repository\AggregateAlreadyExists;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PatchlevelWithdrawalRepository implements WithdrawalRepositoryInterface
{
    /**
     * @param Repository<Withdrawal> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.after_sales.withdrawal.withdrawal.repository')]
        private Repository $repository,
    ) {
    }

    public function has(WithdrawalId $id): bool
    {
        return $this->repository->has($id);
    }

    public function load(WithdrawalId $id): Withdrawal
    {
        try {
            return $this->repository->load($id);
        } catch (AggregateNotFound) {
            throw WithdrawalNotFoundException::forId($id->toString());
        }
    }

    public function save(Withdrawal $withdrawal): void
    {
        try {
            $this->repository->save($withdrawal);
        } catch (AggregateAlreadyExists) {
            throw WithdrawalAlreadyExistsException::forId($withdrawal->id->toString());
        }
    }
}
