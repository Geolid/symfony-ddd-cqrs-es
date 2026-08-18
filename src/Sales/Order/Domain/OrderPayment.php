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
use Sales\Order\Domain\Event\OrderPaymentRefunded;
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
    private OrderPaymentState $state;
    private string $checkoutUrl;

    public function id(): OrderPaymentId
    {
        return $this->id;
    }

    public function checkoutUrl(): string
    {
        return $this->checkoutUrl;
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
        if ($this->state->isCancelled()) {
            $this->recordThat(new OrderPaymentVoided(
                id: $this->id->toString(),
                orderId: $this->orderId,
                reference: $this->reference->toString(),
                voidedAt: $authorizedAt->format(\DateTimeInterface::ATOM),
            ));
        }

        if (!$this->state->isRequested()) {
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
        if (!$this->state->isRequested()) {
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
        if (!$this->state->isAuthorized()) {
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
        if ($this->state->isRequested()) {
            $this->recordThat(new OrderPaymentCancelled(
                id: $this->id->toString(),
                orderId: $this->orderId,
                cancelledAt: $cancelledAt->format(\DateTimeInterface::ATOM),
            ));
        }

        if ($this->state->isAuthorized()) {
            $this->recordThat(new OrderPaymentVoided(
                id: $this->id->toString(),
                orderId: $this->orderId,
                reference: $this->reference->toString(),
                voidedAt: $cancelledAt->format(\DateTimeInterface::ATOM),
            ));
        }

        if ($this->state->isCaptured()) {
            $this->refund($cancelledAt);
        }
    }

    public function refund(\DateTimeImmutable $refundedAt): void
    {
        if (!$this->state->isCaptured()) {
            return;
        }

        $this->recordThat(new OrderPaymentRefundRequested(
            id: $this->id->toString(),
            orderId: $this->orderId,
            reference: $this->reference->toString(),
            requestedAt: $refundedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    public function confirmRefund(\DateTimeImmutable $refundedAt): void
    {
        if (!$this->state->isRefunding()) {
            return;
        }

        $this->recordThat(new OrderPaymentRefunded(
            id: $this->id->toString(),
            orderId: $this->orderId,
            refundedAt: $refundedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    #[Apply]
    private function applyRequested(OrderPaymentRequested $event): void
    {
        $this->id = OrderPaymentId::fromString($event->id);
        $this->orderId = $event->orderId;
        $this->reference = PaymentReference::fromString($event->reference);
        $this->checkoutUrl = $event->checkoutUrl;
        $this->state = OrderPaymentState::REQUESTED;
    }

    #[Apply]
    private function applyAuthorized(OrderPaymentAuthorized $event): void
    {
        $this->state = OrderPaymentState::AUTHORIZED;
    }

    #[Apply]
    private function applyFailed(OrderPaymentFailed $event): void
    {
        $this->state = OrderPaymentState::FAILED;
    }

    #[Apply]
    private function applyCaptured(OrderPaymentCaptured $event): void
    {
        $this->state = OrderPaymentState::CAPTURED;
    }

    #[Apply]
    private function applyCancelled(OrderPaymentCancelled $event): void
    {
        $this->state = OrderPaymentState::CANCELLED;
    }

    #[Apply]
    private function applyVoided(OrderPaymentVoided $event): void
    {
        $this->state = OrderPaymentState::CANCELLED;
    }

    #[Apply]
    private function applyRefundRequested(OrderPaymentRefundRequested $event): void
    {
        $this->state = OrderPaymentState::REFUNDING;
    }

    #[Apply]
    private function applyRefunded(OrderPaymentRefunded $event): void
    {
        $this->state = OrderPaymentState::REFUNDED;
    }
}
