<?php

declare(strict_types=1);

namespace Ordering\Order\Domain;

use Ordering\Order\Domain\Event\OrderCancelled;
use Ordering\Order\Domain\Event\OrderPlaced;
use Ordering\Order\Domain\Exception\OrderAlreadyCancelledException;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate('ordering.order')]
final class Order implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    private OrderId $id;
    private OrderStatus $status;

    public function id(): OrderId
    {
        return $this->id;
    }

    public static function place(
        OrderId $id,
        string $customerId,
        Money $totalAmount,
        \DateTimeImmutable $placedAt,
    ): self {
        $self = new self();
        $self->recordThat(new OrderPlaced(
            id: $id->toString(),
            customerId: $customerId,
            totalAmountInCents: $totalAmount->toCents(),
            placedAt: $placedAt->format('c'),
        ));

        return $self;
    }

    /**
     * @throws OrderAlreadyCancelledException
     */
    public function cancel(\DateTimeImmutable $cancelledAt): void
    {
        if ($this->status->isCancelled()) {
            throw OrderAlreadyCancelledException::forId($this->id);
        }

        $this->recordThat(new OrderCancelled(
            id: $this->id->toString(),
            cancelledAt: $cancelledAt->format('c'),
        ));
    }

    #[Apply]
    private function applyOrderPlaced(OrderPlaced $event): void
    {
        $this->id = OrderId::fromString($event->id);
        $this->status = OrderStatus::PLACED;
    }

    #[Apply]
    private function applyOrderCancelled(OrderCancelled $event): void
    {
        $this->status = OrderStatus::CANCELLED;
    }
}
