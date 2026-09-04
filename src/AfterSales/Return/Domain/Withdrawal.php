<?php

declare(strict_types=1);

namespace AfterSales\Return\Domain;

use AfterSales\Return\Domain\Event\WithdrawalApproved;
use AfterSales\Return\Domain\Event\WithdrawalReceived;
use AfterSales\Return\Domain\Event\WithdrawalRejected;
use AfterSales\Return\Domain\Event\WithdrawalRequested;
use AfterSales\Return\Domain\Exception\CannotRequestWithdrawalForAnotherCustomerException;
use AfterSales\Return\Domain\Exception\WithdrawalNotReceivedException;
use AfterSales\Return\Domain\Exception\WithdrawalWindowExpiredException;
use AfterSales\Return\Domain\Specification\WithdrawalWindowExpiredSpecification;
use AfterSales\Return\Domain\ValueObject\WithdrawalId;
use AfterSales\Return\Domain\ValueObject\WithdrawalState;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Shared\Domain\Specification\CanTransitionToSpecification;
use Shared\Domain\Specification\HasReachedSpecification;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;

/**
 * The right of withdrawal: a time-boxed, no-fault post-delivery cancellation
 * right the merchant decides on (approve/reject) once the goods are physically
 * back.
 */
#[Aggregate('after_sales.return.withdrawal')]
final class Withdrawal implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    /** @var array<string, list<WithdrawalState>> */
    private const array TRANSITIONS = [
        WithdrawalState::REQUESTED->value => [WithdrawalState::RECEIVED],
        WithdrawalState::RECEIVED->value => [WithdrawalState::APPROVED, WithdrawalState::REJECTED],
        WithdrawalState::APPROVED->value => [],
        WithdrawalState::REJECTED->value => [],
    ];

    #[Id]
    public private(set) WithdrawalId $id;
    public private(set) string $orderId;
    public private(set) string $customerId;
    public private(set) PostalAddress $shippingAddress;
    private WithdrawalState $state;

    /**
     * @throws CannotRequestWithdrawalForAnotherCustomerException
     * @throws WithdrawalWindowExpiredException
     */
    public static function request(
        WithdrawalId $id,
        string $orderId,
        string $customerId,
        string $actingCustomerId,
        PostalAddress $shippingAddress,
        \DateTimeImmutable $deliveredAt,
        \DateTimeImmutable $now,
    ): self {
        if ($customerId !== $actingCustomerId) {
            throw CannotRequestWithdrawalForAnotherCustomerException::forId($id);
        }

        if (new WithdrawalWindowExpiredSpecification($now)->isSatisfiedBy($deliveredAt)) {
            throw WithdrawalWindowExpiredException::forId($id);
        }

        $self = new self();
        $self->recordThat(new WithdrawalRequested(
            id: $id->toString(),
            orderId: $orderId,
            customerId: $customerId,
            shippingAddress: $shippingAddress->toArray(),
            requestedAt: $now,
        ));

        return $self;
    }

    public function receive(\DateTimeImmutable $receivedAt): void
    {
        if (!new CanTransitionToSpecification(self::TRANSITIONS, WithdrawalState::RECEIVED)->isSatisfiedBy($this->state)) {
            return;
        }

        $this->recordThat(new WithdrawalReceived(
            id: $this->id->toString(),
            receivedAt: $receivedAt,
        ));
    }

    /**
     * @throws WithdrawalNotReceivedException
     */
    public function approve(\DateTimeImmutable $approvedAt): void
    {
        if (new HasReachedSpecification(self::TRANSITIONS, WithdrawalState::APPROVED)->isSatisfiedBy($this->state)) {
            return;
        }

        if (!new CanTransitionToSpecification(self::TRANSITIONS, WithdrawalState::APPROVED)->isSatisfiedBy($this->state)) {
            throw WithdrawalNotReceivedException::forId($this->id);
        }

        $this->recordThat(new WithdrawalApproved(
            id: $this->id->toString(),
            approvedAt: $approvedAt,
        ));
    }

    /**
     * @throws WithdrawalNotReceivedException
     */
    public function reject(string $reason, \DateTimeImmutable $rejectedAt): void
    {
        if (new HasReachedSpecification(self::TRANSITIONS, WithdrawalState::REJECTED)->isSatisfiedBy($this->state)) {
            return;
        }

        if (!new CanTransitionToSpecification(self::TRANSITIONS, WithdrawalState::REJECTED)->isSatisfiedBy($this->state)) {
            throw WithdrawalNotReceivedException::forId($this->id);
        }

        $this->recordThat(new WithdrawalRejected(
            id: $this->id->toString(),
            reason: $reason,
            rejectedAt: $rejectedAt,
        ));
    }

    #[Apply]
    private function applyRequested(WithdrawalRequested $event): void
    {
        $this->id = WithdrawalId::fromString($event->id);
        $this->orderId = $event->orderId;
        $this->customerId = $event->customerId;
        $this->shippingAddress = PostalAddress::of(
            $event->shippingAddress['recipientName'],
            Address::of($event->shippingAddress['street'], $event->shippingAddress['postalCode'], $event->shippingAddress['city'], $event->shippingAddress['countryCode']),
        );
        $this->state = WithdrawalState::REQUESTED;
    }

    #[Apply]
    private function applyReceived(WithdrawalReceived $event): void
    {
        $this->state = WithdrawalState::RECEIVED;
    }

    #[Apply]
    private function applyApproved(WithdrawalApproved $event): void
    {
        $this->state = WithdrawalState::APPROVED;
    }

    #[Apply]
    private function applyRejected(WithdrawalRejected $event): void
    {
        $this->state = WithdrawalState::REJECTED;
    }
}
