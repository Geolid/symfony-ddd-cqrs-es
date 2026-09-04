<?php

declare(strict_types=1);

namespace Finance\Payment\Infrastructure\EventStore;

use Finance\Payment\Domain\Exception\PaymentAlreadyExistsException;
use Finance\Payment\Domain\Exception\PaymentNotFoundException;
use Finance\Payment\Domain\Payment;
use Finance\Payment\Domain\Repository\PaymentRepositoryInterface;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Patchlevel\EventSourcing\Repository\AggregateAlreadyExists;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PatchlevelPaymentRepository implements PaymentRepositoryInterface
{
    /**
     * @param Repository<Payment> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.finance.payment.payment.repository')]
        private Repository $repository,
    ) {
    }

    public function has(PaymentId $id): bool
    {
        return $this->repository->has($id);
    }

    public function load(PaymentId $id): Payment
    {
        try {
            return $this->repository->load($id);
        } catch (AggregateNotFound) {
            throw PaymentNotFoundException::forId($id->toString());
        }
    }

    public function save(Payment $orderPayment): void
    {
        try {
            $this->repository->save($orderPayment);
        } catch (AggregateAlreadyExists) {
            throw PaymentAlreadyExistsException::forId($orderPayment->id->toString());
        }
    }
}
