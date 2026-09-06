<?php

declare(strict_types=1);

namespace Finance\Payment\Domain;

use Finance\Payment\Domain\Event\PaymentAuthorized;
use Finance\Payment\Domain\Event\PaymentCancelled;
use Finance\Payment\Domain\Event\PaymentCaptured;
use Finance\Payment\Domain\Event\PaymentFailed;
use Finance\Payment\Domain\Event\PaymentRefundConfirmed;
use Finance\Payment\Domain\Event\PaymentRefundFailed;
use Finance\Payment\Domain\Event\PaymentRefundInitiated;
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
use Shared\Domain\Specification\CanTransitionToSpecification;
use Shared\Domain\ValueObject\Money;

#[Aggregate('finance.payment.payment')]
final class Payment implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    /** @var array<string, list<PaymentState>> */
    private const array TRANSITIONS = [
        PaymentState::REQUESTED->value => [PaymentState::AUTHORIZED, PaymentState::FAILED, PaymentState::CANCELLED],
        PaymentState::AUTHORIZED->value => [PaymentState::CAPTURED, PaymentState::FAILED, PaymentState::CANCELLED],
        PaymentState::CAPTURED->value => [PaymentState::REFUNDING],
        PaymentState::REFUNDING->value => [PaymentState::REFUNDED, PaymentState::CAPTURED],
        PaymentState::REFUNDED->value => [],
        PaymentState::FAILED->value => [],
        PaymentState::CANCELLED->value => [],
    ];

    #[Id]
    public private(set) PaymentId $id;
    public private(set) string $checkoutUrl;
    public private(set) PaymentReference $reference;
    private string $orderId;
    private PaymentState $state;
    private ?string $pendingRefundId = null;

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
            amount: $amount,
            reference: $reference,
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
                reference: $this->reference,
                voidedAt: $authorizedAt,
            ));
        }

        if (!new CanTransitionToSpecification(self::TRANSITIONS, PaymentState::AUTHORIZED)->isSatisfiedBy($this->state)) {
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
        if (!new CanTransitionToSpecification(self::TRANSITIONS, PaymentState::FAILED)->isSatisfiedBy($this->state)) {
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
        if (!new CanTransitionToSpecification(self::TRANSITIONS, PaymentState::CAPTURED)->isSatisfiedBy($this->state)) {
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
                reference: $this->reference,
                voidedAt: $cancelledAt,
            ));
        }

        if ($this->state->isCaptured()) {
            $this->recordThat(new PaymentRefundRequired(
                id: $this->id->toString(),
                orderId: $this->orderId,
                reference: $this->reference,
                requiredAt: $cancelledAt,
            ));
        }
    }

    public function requestRefund(string $refundId, \DateTimeImmutable $requestedAt): void
    {
        if (!new CanTransitionToSpecification(self::TRANSITIONS, PaymentState::REFUNDING)->isSatisfiedBy($this->state)) {
            return;
        }

        $this->recordThat(new PaymentRefundInitiated(
            id: $this->id->toString(),
            orderId: $this->orderId,
            refundId: $refundId,
            reference: $this->reference,
            requestedAt: $requestedAt,
        ));
    }

    public function failRefund(string $refundId, \DateTimeImmutable $failedAt): void
    {
        if ($refundId !== $this->pendingRefundId) {
            return;
        }

        $this->recordThat(new PaymentRefundFailed(
            id: $this->id->toString(),
            orderId: $this->orderId,
            refundId: $refundId,
            failedAt: $failedAt,
        ));
    }

    public function confirmRefund(string $refundId, \DateTimeImmutable $confirmedAt): void
    {
        if ($refundId !== $this->pendingRefundId) {
            return;
        }

        $this->recordThat(new PaymentRefundConfirmed(
            id: $this->id->toString(),
            orderId: $this->orderId,
            refundId: $refundId,
            confirmedAt: $confirmedAt,
        ));
    }

    #[Apply]
    private function applyRequested(PaymentRequested $event): void
    {
        $this->id = PaymentId::fromString($event->id);
        $this->orderId = $event->orderId;
        $this->reference = $event->reference;
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
    private function applyRefundInitiated(PaymentRefundInitiated $event): void
    {
        $this->state = PaymentState::REFUNDING;
        $this->pendingRefundId = $event->refundId;
    }

    #[Apply]
    private function applyRefundFailed(PaymentRefundFailed $event): void
    {
        $this->state = PaymentState::CAPTURED;
        $this->pendingRefundId = null;
    }

    #[Apply]
    private function applyRefundConfirmed(PaymentRefundConfirmed $event): void
    {
        $this->state = PaymentState::REFUNDED;
        $this->pendingRefundId = null;
    }
}
