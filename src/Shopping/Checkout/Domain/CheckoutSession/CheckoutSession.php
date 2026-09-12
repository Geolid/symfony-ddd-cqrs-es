<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\CheckoutSession;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Shared\Domain\Specification\CanTransitionToSpecification;
use Shared\Domain\ValueObject\PostalAddress;
use Shopping\Checkout\Domain\CheckoutSession\Event\CheckoutSessionConsumed;
use Shopping\Checkout\Domain\CheckoutSession\Event\CheckoutSessionExpired;
use Shopping\Checkout\Domain\CheckoutSession\Event\CheckoutSessionOpened;
use Shopping\Checkout\Domain\CheckoutSession\Event\CheckoutSessionStaled;
use Shopping\Checkout\Domain\CheckoutSession\Exception\CheckoutSessionEmptyException;
use Shopping\Checkout\Domain\CheckoutSession\ValueObject\CheckoutSessionId;
use Shopping\Checkout\Domain\CheckoutSession\ValueObject\CheckoutSessionState;

#[Aggregate('shopping.checkout.checkout_session')]
final class CheckoutSession implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    public const int TTL_MINUTES = 30;

    /** @var array<string, list<CheckoutSessionState>> */
    private const array OPERATIONAL_TRANSITIONS = [
        CheckoutSessionState::OPEN->value => [CheckoutSessionState::EXPIRED, CheckoutSessionState::STALE, CheckoutSessionState::CONSUMED],
        CheckoutSessionState::EXPIRED->value => [],
        CheckoutSessionState::STALE->value => [],
        CheckoutSessionState::CONSUMED->value => [],
    ];

    #[Id]
    public private(set) CheckoutSessionId $id;
    private CheckoutSessionState $operationalState;

    /**
     * @param list<array{productId: string, label: string, unitPriceInCents: int, quantity: int}> $lines
     *
     * @throws CheckoutSessionEmptyException
     */
    public static function open(
        CheckoutSessionId $id,
        string $cartId,
        string $shopperId,
        array $lines,
        PostalAddress $shippingAddress,
        PostalAddress $billingAddress,
        int $totalAmountInCents,
        \DateTimeImmutable $openedAt,
    ): self {
        if ([] === $lines) {
            throw CheckoutSessionEmptyException::forId($id);
        }

        $self = new self();
        $self->recordThat(new CheckoutSessionOpened(
            id: $id->toString(),
            cartId: $cartId,
            shopperId: $shopperId,
            lines: $lines,
            shippingAddress: $shippingAddress,
            billingAddress: $billingAddress,
            totalAmountInCents: $totalAmountInCents,
            openedAt: $openedAt,
        ));

        return $self;
    }

    public function expire(\DateTimeImmutable $expiredAt): void
    {
        if (!$this->canTransitionOperationalTo(CheckoutSessionState::EXPIRED)) {
            return;
        }

        $this->recordThat(new CheckoutSessionExpired(
            id: $this->id->toString(),
            expiredAt: $expiredAt,
        ));
    }

    public function stale(\DateTimeImmutable $staledAt): void
    {
        if (!$this->canTransitionOperationalTo(CheckoutSessionState::STALE)) {
            return;
        }

        $this->recordThat(new CheckoutSessionStaled(
            id: $this->id->toString(),
            staledAt: $staledAt,
        ));
    }

    public function consume(\DateTimeImmutable $consumedAt): void
    {
        if (!$this->canTransitionOperationalTo(CheckoutSessionState::CONSUMED)) {
            return;
        }

        $this->recordThat(new CheckoutSessionConsumed(
            id: $this->id->toString(),
            consumedAt: $consumedAt,
        ));
    }

    private function canTransitionOperationalTo(CheckoutSessionState $target): bool
    {
        return new CanTransitionToSpecification(self::OPERATIONAL_TRANSITIONS, $target)->isSatisfiedBy($this->operationalState);
    }

    #[Apply]
    private function applyOpened(CheckoutSessionOpened $event): void
    {
        $this->id = CheckoutSessionId::fromString($event->id);
        $this->operationalState = CheckoutSessionState::OPEN;
    }

    #[Apply]
    private function applyExpired(CheckoutSessionExpired $event): void
    {
        $this->operationalState = CheckoutSessionState::EXPIRED;
    }

    #[Apply]
    private function applyStaled(CheckoutSessionStaled $event): void
    {
        $this->operationalState = CheckoutSessionState::STALE;
    }

    #[Apply]
    private function applyConsumed(CheckoutSessionConsumed $event): void
    {
        $this->operationalState = CheckoutSessionState::CONSUMED;
    }
}
