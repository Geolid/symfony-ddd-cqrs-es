<?php

declare(strict_types=1);

namespace Finance\Refund\Domain;

use Finance\Refund\Domain\Event\RefundConfirmed;
use Finance\Refund\Domain\Event\RefundFailed;
use Finance\Refund\Domain\Event\RefundInitiated;
use Finance\Refund\Domain\ValueObject\RefundId;
use Finance\Refund\Domain\ValueObject\RefundState;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Shared\Domain\Specification\CanTransitionToSpecification;
use Shared\Domain\ValueObject\Money;

#[Aggregate('finance.refund.refund')]
final class Refund implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    /** @var array<string, list<RefundState>> */
    private const array TRANSITIONS = [
        RefundState::INITIATED->value => [RefundState::REFUNDED, RefundState::FAILED],
        RefundState::REFUNDED->value => [],
        RefundState::FAILED->value => [],
    ];

    #[Id]
    public private(set) RefundId $id;
    public private(set) string $paymentId;
    public private(set) string $orderId;
    public private(set) int $amountInCents;
    private RefundState $state;

    public static function initiate(
        RefundId $id,
        string $paymentId,
        string $orderId,
        Money $amount,
        \DateTimeImmutable $initiatedAt,
    ): self {
        $self = new self();
        $self->recordThat(new RefundInitiated(
            id: $id->toString(),
            paymentId: $paymentId,
            orderId: $orderId,
            amount: $amount,
            initiatedAt: $initiatedAt,
        ));

        return $self;
    }

    public function confirm(\DateTimeImmutable $refundedAt): void
    {
        if (!new CanTransitionToSpecification(self::TRANSITIONS, RefundState::REFUNDED)->isSatisfiedBy($this->state)) {
            return;
        }

        $this->recordThat(new RefundConfirmed(
            id: $this->id->toString(),
            refundedAt: $refundedAt,
        ));
    }

    public function fail(\DateTimeImmutable $failedAt): void
    {
        if (!new CanTransitionToSpecification(self::TRANSITIONS, RefundState::FAILED)->isSatisfiedBy($this->state)) {
            return;
        }

        $this->recordThat(new RefundFailed(
            id: $this->id->toString(),
            failedAt: $failedAt,
        ));
    }

    #[Apply]
    private function applyInitiated(RefundInitiated $event): void
    {
        $this->id = RefundId::fromString($event->id);
        $this->paymentId = $event->paymentId;
        $this->orderId = $event->orderId;
        $this->amountInCents = $event->amount->cents;
        $this->state = RefundState::INITIATED;
    }

    #[Apply]
    private function applyConfirmed(RefundConfirmed $event): void
    {
        $this->state = RefundState::REFUNDED;
    }

    #[Apply]
    private function applyFailed(RefundFailed $event): void
    {
        $this->state = RefundState::FAILED;
    }
}
