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
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\PostalAddress;
use Shopping\Checkout\Domain\CheckoutSession\Event\CheckoutSessionCompleted;
use Shopping\Checkout\Domain\CheckoutSession\Event\CheckoutSessionExpired;
use Shopping\Checkout\Domain\CheckoutSession\Event\CheckoutSessionOpened;
use Shopping\Checkout\Domain\CheckoutSession\Event\CheckoutSessionStaled;
use Shopping\Checkout\Domain\CheckoutSession\Exception\CheckoutSessionEmptyException;
use Shopping\Checkout\Domain\CheckoutSession\ValueObject\CheckoutItem;
use Shopping\Checkout\Domain\CheckoutSession\ValueObject\CheckoutSessionId;
use Shopping\Checkout\Domain\CheckoutSession\ValueObject\CheckoutSessionState;

#[Aggregate('shopping.checkout.checkout_session')]
final class CheckoutSession implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    public const int TTL_MINUTES = 30;

    /** @var array<string, list<CheckoutSessionState>> */
    private const array OPERATIONAL_TRANSITIONS = [
        CheckoutSessionState::OPEN->value => [CheckoutSessionState::EXPIRED, CheckoutSessionState::STALE, CheckoutSessionState::COMPLETED],
        CheckoutSessionState::EXPIRED->value => [],
        CheckoutSessionState::STALE->value => [],
        CheckoutSessionState::COMPLETED->value => [],
    ];

    #[Id]
    public private(set) CheckoutSessionId $id;
    private CheckoutSessionState $operationalState;

    /**
     * @param list<CheckoutItem> $items
     *
     * @throws CheckoutSessionEmptyException
     */
    public static function open(
        CheckoutSessionId $id,
        string $cartId,
        string $shopperId,
        array $items,
        PostalAddress $shippingAddress,
        PostalAddress $billingAddress,
        \DateTimeImmutable $openedAt,
    ): self {
        if ([] === $items) {
            throw CheckoutSessionEmptyException::forId($id);
        }

        $self = new self();
        $self->recordThat(new CheckoutSessionOpened(
            id: $id->toString(),
            cartId: $cartId,
            shopperId: $shopperId,
            items: $items,
            shippingAddress: $shippingAddress,
            billingAddress: $billingAddress,
            openedAt: $openedAt,
            totalAmount: self::sumItems($items),
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

    /**
     * @param list<CheckoutItem> $items
     */
    public function complete(
        string $cartId,
        string $shopperId,
        array $items,
        PostalAddress $shippingAddress,
        PostalAddress $billingAddress,
        string $paymentId,
        \DateTimeImmutable $completedAt,
    ): void {
        if (!$this->canTransitionOperationalTo(CheckoutSessionState::COMPLETED)) {
            return;
        }

        $this->recordThat(new CheckoutSessionCompleted(
            id: $this->id->toString(),
            cartId: $cartId,
            shopperId: $shopperId,
            items: $items,
            shippingAddress: $shippingAddress,
            billingAddress: $billingAddress,
            totalAmount: self::sumItems($items),
            paymentId: $paymentId,
            completedAt: $completedAt,
        ));
    }

    private function canTransitionOperationalTo(CheckoutSessionState $target): bool
    {
        return new CanTransitionToSpecification(self::OPERATIONAL_TRANSITIONS, $target)->isSatisfiedBy($this->operationalState);
    }

    /**
     * @param list<CheckoutItem> $items
     */
    private static function sumItems(array $items): Money
    {
        return array_reduce(
            $items,
            static fn (Money $carry, CheckoutItem $item): Money => $carry->plus($item->subtotal()),
            Money::fromCents(0),
        );
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
    private function applyCompleted(CheckoutSessionCompleted $event): void
    {
        $this->operationalState = CheckoutSessionState::COMPLETED;
    }
}
