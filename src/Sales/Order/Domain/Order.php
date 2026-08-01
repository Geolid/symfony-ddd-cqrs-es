<?php

declare(strict_types=1);

namespace Sales\Order\Domain;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Sales\Order\Domain\Event\OrderCancelled;
use Sales\Order\Domain\Event\OrderPlaced;
use Sales\Order\Domain\Exception\OrderAlreadyCancelledException;
use Sales\Order\Domain\Exception\OrderWithoutLineException;

#[Aggregate('sales.order.order')]
final class Order implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    private OrderId $id;
    private ?string $buyerAddress;
    private Money $totalAmount;
    private OrderStatus $status;

    public function id(): OrderId
    {
        return $this->id;
    }

    public function buyerAddress(): ?string
    {
        return $this->buyerAddress;
    }

    public function totalAmount(): Money
    {
        return $this->totalAmount;
    }

    /**
     * @param list<OrderLine> $lines
     *
     * @throws OrderWithoutLineException
     */
    public static function place(
        OrderId $id,
        string $customerId,
        ?string $buyerAddress,
        array $lines,
        \DateTimeImmutable $placedAt,
    ): self {
        if ([] === $lines) {
            throw OrderWithoutLineException::forId($id);
        }

        $total = array_reduce(
            $lines,
            static fn (Money $carry, OrderLine $line): Money => $carry->plus($line->total()),
            Money::fromCents(0),
        );

        $self = new self();
        $self->recordThat(new OrderPlaced(
            id: $id->toString(),
            customerId: $customerId,
            buyerAddress: $buyerAddress,
            lines: array_values(array_map(
                static fn (OrderLine $line): array => [
                    'label' => $line->label,
                    'quantity' => $line->quantity,
                    'unitAmountInCents' => $line->unitAmount->toCents(),
                ],
                $lines,
            )),
            totalAmountInCents: $total->toCents(),
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
        $this->buyerAddress = $event->buyerAddress;
        $this->totalAmount = Money::fromCents($event->totalAmountInCents);
        $this->status = OrderStatus::PLACED;
    }

    #[Apply]
    private function applyOrderCancelled(OrderCancelled $event): void
    {
        $this->status = OrderStatus::CANCELLED;
    }
}
