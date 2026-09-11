<?php

declare(strict_types=1);

namespace Finance\Payment\Domain;

use Finance\Payment\Domain\Event\PaymentAbandoned;
use Finance\Payment\Domain\Event\PaymentAuthorized;
use Finance\Payment\Domain\Event\PaymentCaptured;
use Finance\Payment\Domain\Event\PaymentFailed;
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
    private const array OPERATIONAL_TRANSITIONS = [
        PaymentState::REQUESTED->value => [PaymentState::AUTHORIZED, PaymentState::ABANDONED],
        PaymentState::AUTHORIZED->value => [PaymentState::CAPTURED, PaymentState::FAILED, PaymentState::VOIDED],
        PaymentState::CAPTURED->value => [],
        PaymentState::FAILED->value => [],
        PaymentState::ABANDONED->value => [],
        PaymentState::VOIDED->value => [],
    ];

    #[Id]
    public private(set) PaymentId $id;
    public private(set) string $checkoutUrl;
    public private(set) PaymentReference $reference;
    private string $checkoutSessionId;
    private PaymentState $operationalState;

    public static function request(
        PaymentId $id,
        string $checkoutSessionId,
        Money $amount,
        PaymentReference $reference,
        string $checkoutUrl,
        \DateTimeImmutable $requestedAt,
    ): self {
        $self = new self();
        $self->recordThat(new PaymentRequested(
            id: $id->toString(),
            checkoutSessionId: $checkoutSessionId,
            amount: $amount,
            reference: $reference,
            checkoutUrl: $checkoutUrl,
            requestedAt: $requestedAt,
        ));

        return $self;
    }

    public function authorize(\DateTimeImmutable $authorizedAt): void
    {
        if ($this->operationalState->isAbandoned()) {
            $this->recordThat(new PaymentVoided(
                id: $this->id->toString(),
                reference: $this->reference,
                voidedAt: $authorizedAt,
            ));
        }

        if (!$this->canTransitionOperationalTo(PaymentState::AUTHORIZED)) {
            return;
        }

        $this->recordThat(new PaymentAuthorized(
            id: $this->id->toString(),
            checkoutSessionId: $this->checkoutSessionId,
            authorizedAt: $authorizedAt,
        ));
    }

    public function fail(string $orderId, \DateTimeImmutable $failedAt): void
    {
        if (!$this->canTransitionOperationalTo(PaymentState::FAILED)) {
            return;
        }

        $this->recordThat(new PaymentFailed(
            id: $this->id->toString(),
            orderId: $orderId,
            failedAt: $failedAt,
        ));
    }

    public function capture(string $orderId, \DateTimeImmutable $capturedAt): void
    {
        if (!$this->canTransitionOperationalTo(PaymentState::CAPTURED)) {
            return;
        }

        $this->recordThat(new PaymentCaptured(
            id: $this->id->toString(),
            orderId: $orderId,
            capturedAt: $capturedAt,
        ));
    }

    public function abandon(\DateTimeImmutable $abandonedAt): void
    {
        if (!$this->canTransitionOperationalTo(PaymentState::ABANDONED)) {
            return;
        }

        $this->recordThat(new PaymentAbandoned(
            id: $this->id->toString(),
            checkoutSessionId: $this->checkoutSessionId,
            abandonedAt: $abandonedAt,
        ));
    }

    public function void(\DateTimeImmutable $voidedAt): void
    {
        if (!$this->canTransitionOperationalTo(PaymentState::VOIDED)) {
            return;
        }

        $this->recordThat(new PaymentVoided(
            id: $this->id->toString(),
            reference: $this->reference,
            voidedAt: $voidedAt,
        ));
    }

    private function canTransitionOperationalTo(PaymentState $target): bool
    {
        return new CanTransitionToSpecification(self::OPERATIONAL_TRANSITIONS, $target)->isSatisfiedBy($this->operationalState);
    }

    #[Apply]
    private function applyRequested(PaymentRequested $event): void
    {
        $this->id = PaymentId::fromString($event->id);
        $this->checkoutSessionId = $event->checkoutSessionId;
        $this->reference = $event->reference;
        $this->checkoutUrl = $event->checkoutUrl;
        $this->operationalState = PaymentState::REQUESTED;
    }

    #[Apply]
    private function applyAuthorized(PaymentAuthorized $event): void
    {
        $this->operationalState = PaymentState::AUTHORIZED;
    }

    #[Apply]
    private function applyFailed(PaymentFailed $event): void
    {
        $this->operationalState = PaymentState::FAILED;
    }

    #[Apply]
    private function applyCaptured(PaymentCaptured $event): void
    {
        $this->operationalState = PaymentState::CAPTURED;
    }

    #[Apply]
    private function applyAbandoned(PaymentAbandoned $event): void
    {
        $this->operationalState = PaymentState::ABANDONED;
    }

    #[Apply]
    private function applyVoided(PaymentVoided $event): void
    {
        $this->operationalState = PaymentState::VOIDED;
    }
}
