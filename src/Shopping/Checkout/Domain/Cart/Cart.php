<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Cart;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Shared\Domain\Specification\CanTransitionToSpecification;
use Shopping\Checkout\Domain\Cart\Event\CartProductAdded;
use Shopping\Checkout\Domain\Cart\Event\CartProductQuantityChanged;
use Shopping\Checkout\Domain\Cart\Event\CartProductRemoved;
use Shopping\Checkout\Domain\Cart\Event\CartPurchased;
use Shopping\Checkout\Domain\Cart\Event\CartStarted;
use Shopping\Checkout\Domain\Cart\Exception\CartAlreadyPurchasedException;
use Shopping\Checkout\Domain\Cart\Exception\CartProductNotFoundException;
use Shopping\Checkout\Domain\Cart\ValueObject\CartId;
use Shopping\Checkout\Domain\Cart\ValueObject\CartState;
use Shopping\Checkout\Domain\Cart\ValueObject\Quantity;

#[Aggregate('shopping.checkout.cart')]
final class Cart implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    /** @var array<string, list<CartState>> */
    private const array OPERATIONAL_TRANSITIONS = [
        CartState::ACTIVE->value => [CartState::PURCHASED],
        CartState::PURCHASED->value => [],
    ];

    #[Id]
    public private(set) CartId $id;
    private CartState $operationalState;
    /** @var array<string, string> */
    private array $productIds = [];

    public static function start(CartId $id, string $customerId, \DateTimeImmutable $startedAt): self
    {
        $self = new self();
        $self->recordThat(new CartStarted(
            id: $id,
            customerId: $customerId,
            startedAt: $startedAt,
        ));

        return $self;
    }

    /**
     * @throws CartAlreadyPurchasedException
     */
    public function addProduct(string $productId, Quantity $quantity, \DateTimeImmutable $now): void
    {
        $this->guardActive();

        $this->recordThat(new CartProductAdded(
            id: $this->id,
            productId: $productId,
            quantity: $quantity,
            addedAt: $now,
        ));
    }

    /**
     * @throws CartAlreadyPurchasedException
     * @throws CartProductNotFoundException
     */
    public function removeProduct(string $productId, \DateTimeImmutable $now): void
    {
        $this->guardActive();

        if (!isset($this->productIds[$productId])) {
            throw CartProductNotFoundException::forProductId($productId);
        }

        $this->recordThat(new CartProductRemoved(
            id: $this->id,
            productId: $productId,
            removedAt: $now,
        ));
    }

    /**
     * @throws CartAlreadyPurchasedException
     * @throws CartProductNotFoundException
     */
    public function changeQuantity(string $productId, Quantity $quantity, \DateTimeImmutable $now): void
    {
        $this->guardActive();

        if (!isset($this->productIds[$productId])) {
            throw CartProductNotFoundException::forProductId($productId);
        }

        $this->recordThat(new CartProductQuantityChanged(
            id: $this->id,
            productId: $productId,
            quantity: $quantity,
            changedAt: $now,
        ));
    }

    public function purchase(\DateTimeImmutable $now): void
    {
        if (!$this->canTransitionOperationalTo(CartState::PURCHASED)) {
            return;
        }

        $this->recordThat(new CartPurchased(
            id: $this->id,
            purchasedAt: $now,
        ));
    }

    /**
     * @throws CartAlreadyPurchasedException
     */
    private function guardActive(): void
    {
        if ($this->operationalState->isPurchased()) {
            throw CartAlreadyPurchasedException::forId($this->id);
        }
    }

    private function canTransitionOperationalTo(CartState $target): bool
    {
        return new CanTransitionToSpecification(self::OPERATIONAL_TRANSITIONS, $target)->isSatisfiedBy($this->operationalState);
    }

    #[Apply]
    private function applyStarted(CartStarted $event): void
    {
        $this->id = $event->id;
        $this->operationalState = CartState::ACTIVE;
    }

    #[Apply]
    private function applyProductAdded(CartProductAdded $event): void
    {
        $this->productIds[$event->productId] = $event->productId;
    }

    #[Apply]
    private function applyProductRemoved(CartProductRemoved $event): void
    {
        unset($this->productIds[$event->productId]);
    }

    #[Apply]
    private function applyProductQuantityChanged(CartProductQuantityChanged $event): void
    {
        // Quantity is never stored on Cart itself, only on the event — nothing to apply.
    }

    #[Apply]
    private function applyPurchased(CartPurchased $event): void
    {
        $this->operationalState = CartState::PURCHASED;
    }
}
