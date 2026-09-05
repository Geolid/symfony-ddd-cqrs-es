<?php

declare(strict_types=1);

namespace Finance\Payment\Domain;

use Finance\Payment\Domain\Event\PaymentAuthorized;
use Finance\Payment\Domain\Event\PaymentCancelled;
use Finance\Payment\Domain\Event\PaymentCaptured;
use Finance\Payment\Domain\Event\PaymentFailed;
use Finance\Payment\Domain\Event\PaymentRefundRejected;
use Finance\Payment\Domain\Event\PaymentRefundRequired;
use Finance\Payment\Domain\Event\PaymentRequested;
use Finance\Payment\Domain\Event\PaymentVoided;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Finance\Payment\Domain\ValueObject\PaymentReference;
use Finance\Payment\Domain\ValueObject\PaymentState;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Shared\Domain\ValueObject\Money;

#[Aggregate('finance.payment.payment')]
final class Payment implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    public private(set) PaymentId $id;
    public private(set) string $checkoutUrl;
    public private(set) PaymentReference $reference;
    private string $orderId;
    private PaymentState $state;

    public static function request(
        PaymentId $id,
        string $orderId,
        Money $amount,
        PaymentReference $reference,
        string $checkoutUrl,
        \DateTimeImmutable $requestedAt,
    ): self {
        $self = new self();
        $self->recordThat(new PaymentRequested(
            id: $id->toString(),
            orderId: $orderId,
            amountInCents: $amount->cents,
            reference: $reference->value,
            checkoutUrl: $checkoutUrl,
            requestedAt: $requestedAt,
        ));

        return $self;
    }

    public function authorize(\DateTimeImmutable $authorizedAt): void
    {
        if ($this->state->isCancelled()) {
            $this->recordThat(new PaymentVoided(
                id: $this->id->toString(),
                orderId: $this->orderId,
                reference: $this->reference->value,
                voidedAt: $authorizedAt,
            ));
        }

        if (!$this->state->isRequested()) {
            return;
        }

        $this->recordThat(new PaymentAuthorized(
            id: $this->id->toString(),
            orderId: $this->orderId,
            authorizedAt: $authorizedAt,
        ));
    }

    public function fail(\DateTimeImmutable $failedAt): void
    {
        if (!$this->state->isRequested() && !$this->state->isAuthorized()) {
            return;
        }

        $this->recordThat(new PaymentFailed(
            id: $this->id->toString(),
            orderId: $this->orderId,
            failedAt: $failedAt,
        ));
    }

    public function capture(\DateTimeImmutable $capturedAt): void
    {
        if (!$this->state->isAuthorized()) {
            return;
        }

        $this->recordThat(new PaymentCaptured(
            id: $this->id->toString(),
            orderId: $this->orderId,
            capturedAt: $capturedAt,
        ));
    }

    public function cancel(\DateTimeImmutable $cancelledAt): void
    {
        if ($this->state->isRequested()) {
            $this->recordThat(new PaymentCancelled(
                id: $this->id->toString(),
                orderId: $this->orderId,
                cancelledAt: $cancelledAt,
            ));
        }

        if ($this->state->isAuthorized()) {
            $this->recordThat(new PaymentVoided(
                id: $this->id->toString(),
                orderId: $this->orderId,
                reference: $this->reference->value,
                voidedAt: $cancelledAt,
            ));
        }

        if ($this->state->isCaptured()) {
            $this->recordThat(new PaymentRefundRequired(
                id: $this->id->toString(),
                orderId: $this->orderId,
                reference: $this->reference->value,
                requiredAt: $cancelledAt,
            ));
        }
    }

    public function rejectRefund(\DateTimeImmutable $rejectedAt): void
    {
        $this->recordThat(new PaymentRefundRejected(
            id: $this->id->toString(),
            orderId: $this->orderId,
            rejectedAt: $rejectedAt,
        ));
    }

    #[Apply]
    private function applyRequested(PaymentRequested $event): void
    {
        $this->id = PaymentId::fromString($event->id);
        $this->orderId = $event->orderId;
        $this->reference = PaymentReference::fromString($event->reference);
        $this->checkoutUrl = $event->checkoutUrl;
        $this->state = PaymentState::REQUESTED;
    }

    #[Apply]
    private function applyAuthorized(PaymentAuthorized $event): void
    {
        $this->state = PaymentState::AUTHORIZED;
    }

    #[Apply]
    private function applyFailed(PaymentFailed $event): void
    {
        $this->state = PaymentState::FAILED;
    }

    #[Apply]
    private function applyCaptured(PaymentCaptured $event): void
    {
        $this->state = PaymentState::CAPTURED;
    }

    #[Apply]
    private function applyCancelled(PaymentCancelled $event): void
    {
        $this->state = PaymentState::CANCELLED;
    }

    #[Apply]
    private function applyVoided(PaymentVoided $event): void
    {
        $this->state = PaymentState::CANCELLED;
    }

    #[Apply]
    private function applyRefundRequired(PaymentRefundRequired $event): void
    {
    }

    #[Apply]
    private function applyRefundRejected(PaymentRefundRejected $event): void
    {
    }
}
