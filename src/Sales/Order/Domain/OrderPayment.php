<?php

declare(strict_types=1);

namespace Sales\Order\Domain;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Sales\Order\Domain\Event\OrderPaymentCaptured;
use Sales\Order\Domain\Event\OrderPaymentRequested;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Sales\Order\Domain\ValueObject\OrderPaymentState;
use Sales\Order\Domain\ValueObject\PaymentReference;
use Shared\Domain\ValueObject\Money;

#[Aggregate('sales.order.payment')]
final class OrderPayment implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    private OrderPaymentId $id;
    private string $orderId;
    private OrderPaymentState $status;

    public function id(): OrderPaymentId
    {
        return $this->id;
    }

    public function status(): OrderPaymentState
    {
        return $this->status;
    }

    public static function request(
        OrderPaymentId $id,
        string $orderId,
        Money $amount,
        PaymentReference $reference,
        string $checkoutUrl,
        \DateTimeImmutable $requestedAt,
    ): self {
        $self = new self();
        $self->recordThat(new OrderPaymentRequested(
            id: $id->toString(),
            orderId: $orderId,
            amountInCents: $amount->toCents(),
            reference: $reference->toString(),
            checkoutUrl: $checkoutUrl,
            requestedAt: $requestedAt->format(\DateTimeInterface::ATOM),
        ));

        return $self;
    }

    public function capture(\DateTimeImmutable $capturedAt): void
    {
        if (!$this->status->isRequested()) {
            return;
        }

        $this->recordThat(new OrderPaymentCaptured(
            id: $this->id->toString(),
            orderId: $this->orderId,
            capturedAt: $capturedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    #[Apply]
    private function applyOrderPaymentRequested(OrderPaymentRequested $event): void
    {
        $this->id = OrderPaymentId::fromString($event->id);
        $this->orderId = $event->orderId;
        $this->status = OrderPaymentState::REQUESTED;
    }

    #[Apply]
    private function applyOrderPaymentCaptured(OrderPaymentCaptured $event): void
    {
        $this->status = OrderPaymentState::CAPTURED;
    }
}
