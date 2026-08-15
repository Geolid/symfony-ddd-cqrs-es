<?php

declare(strict_types=1);

namespace Sales\Order\Domain;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Sales\Order\Domain\Event\OrderPaymentAuthorized;
use Sales\Order\Domain\Event\OrderPaymentCancelled;
use Sales\Order\Domain\Event\OrderPaymentCaptured;
use Sales\Order\Domain\Event\OrderPaymentFailed;
use Sales\Order\Domain\Event\OrderPaymentRefundRequested;
use Sales\Order\Domain\Event\OrderPaymentRequested;
use Sales\Order\Domain\Event\OrderPaymentVoided;
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
    private PaymentReference $reference;
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

    public function authorize(\DateTimeImmutable $authorizedAt): void
    {
        if ($this->status->isCancelled()) {
            $this->recordThat(new OrderPaymentVoided(
                id: $this->id->toString(),
                orderId: $this->orderId,
                reference: $this->reference->toString(),
                voidedAt: $authorizedAt->format(\DateTimeInterface::ATOM),
            ));
        }

        if (!$this->status->isRequested()) {
            return;
        }

        $this->recordThat(new OrderPaymentAuthorized(
            id: $this->id->toString(),
            orderId: $this->orderId,
            authorizedAt: $authorizedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    public function fail(\DateTimeImmutable $failedAt): void
    {
        if (!$this->status->isRequested()) {
            return;
        }

        $this->recordThat(new OrderPaymentFailed(
            id: $this->id->toString(),
            orderId: $this->orderId,
            failedAt: $failedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    public function capture(\DateTimeImmutable $capturedAt): void
    {
        if (!$this->status->isAuthorized()) {
            return;
        }

        $this->recordThat(new OrderPaymentCaptured(
            id: $this->id->toString(),
            orderId: $this->orderId,
            capturedAt: $capturedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    public function cancel(\DateTimeImmutable $cancelledAt): void
    {
        if ($this->status->isRequested()) {
            $this->recordThat(new OrderPaymentCancelled(
                id: $this->id->toString(),
                orderId: $this->orderId,
                cancelledAt: $cancelledAt->format(\DateTimeInterface::ATOM),
            ));

            return;
        }

        if ($this->status->isAuthorized()) {
            $this->recordThat(new OrderPaymentVoided(
                id: $this->id->toString(),
                orderId: $this->orderId,
                reference: $this->reference->toString(),
                voidedAt: $cancelledAt->format(\DateTimeInterface::ATOM),
            ));
        }

        if ($this->status->isCaptured()) {
            $this->refund($cancelledAt);
        }
    }

    public function refund(\DateTimeImmutable $refundedAt): void
    {
        if (!$this->status->isCaptured()) {
            return;
        }

        $this->recordThat(new OrderPaymentRefundRequested(
            id: $this->id->toString(),
            orderId: $this->orderId,
            reference: $this->reference->toString(),
            refundedAt: $refundedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    #[Apply]
    private function applyOrderPaymentRequested(OrderPaymentRequested $event): void
    {
        $this->id = OrderPaymentId::fromString($event->id);
        $this->orderId = $event->orderId;
        $this->reference = PaymentReference::fromString($event->reference);
        $this->status = OrderPaymentState::REQUESTED;
    }

    #[Apply]
    private function applyOrderPaymentAuthorized(OrderPaymentAuthorized $event): void
    {
        $this->status = OrderPaymentState::AUTHORIZED;
    }

    #[Apply]
    private function applyOrderPaymentFailed(OrderPaymentFailed $event): void
    {
        $this->status = OrderPaymentState::FAILED;
    }

    #[Apply]
    private function applyOrderPaymentCaptured(OrderPaymentCaptured $event): void
    {
        $this->status = OrderPaymentState::CAPTURED;
    }

    #[Apply]
    private function applyOrderPaymentCancelled(OrderPaymentCancelled $event): void
    {
        $this->status = OrderPaymentState::CANCELLED;
    }

    #[Apply]
    private function applyOrderPaymentVoided(OrderPaymentVoided $event): void
    {
        $this->status = OrderPaymentState::CANCELLED;
    }

    #[Apply]
    private function applyOrderPaymentRefundRequested(OrderPaymentRefundRequested $event): void
    {
        $this->status = OrderPaymentState::REFUNDING;
    }
}
