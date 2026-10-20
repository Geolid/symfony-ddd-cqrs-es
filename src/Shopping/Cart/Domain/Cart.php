<?php

declare(strict_types=1);

namespace Shopping\Cart\Domain;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Shared\Domain\ValueObject\Quantity;
use Shared\Domain\ValueObject\StateTransitions;
use Shopping\Cart\Domain\Entity\CartLine;
use Shopping\Cart\Domain\Event\CartProductAdded;
use Shopping\Cart\Domain\Event\CartProductQuantityChanged;
use Shopping\Cart\Domain\Event\CartProductRemoved;
use Shopping\Cart\Domain\Event\CartPurchased;
use Shopping\Cart\Domain\Event\CartStarted;
use Shopping\Cart\Domain\Exception\CartAlreadyPurchasedException;
use Shopping\Cart\Domain\Exception\CartProductNotFoundException;
use Shopping\Cart\Domain\ValueObject\CartId;
use Shopping\Cart\Domain\ValueObject\CartLineId;
use Shopping\Cart\Domain\ValueObject\CartState;

#[Aggregate('shopping.cart.cart')]
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
    public private(set) string $customerId;
    public private(set) \DateTimeImmutable $startedAt;
    public private(set) CartState $operationalState;
    /** @var array<string, CartLine> */
    public private(set) array $lines = [];
    public private(set) int $lineSequence = 0;
    public private(set) ?\DateTimeImmutable $purchasedAt = null;

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
    public function addProduct(string $productId, Quantity $quantity, \DateTimeImmutable $addedAt): void
    {
        $this->guardActive();

        $line = $this->lineOf($productId);

        if ($line instanceof CartLine) {
            $this->recordThat(new CartProductQuantityChanged(
                id: $this->id,
                lineId: $line->id,
                productId: $productId,
                quantity: $line->quantity->plus($quantity),
                changedAt: $addedAt,
            ));

            return;
        }

        $this->recordThat(new CartProductAdded(
            id: $this->id,
            lineId: CartLineId::forCart($this->id->toString(), $this->lineSequence),
            productId: $productId,
            quantity: $quantity,
            addedAt: $addedAt,
        ));
    }

    /**
     * @throws CartAlreadyPurchasedException
     * @throws CartProductNotFoundException
     */
    public function removeProduct(string $productId, \DateTimeImmutable $removedAt): void
    {
        $this->guardActive();

        $line = $this->lineOf($productId) ?? throw CartProductNotFoundException::forProductId($productId);

        $this->recordThat(new CartProductRemoved(
            id: $this->id,
            lineId: $line->id,
            productId: $productId,
            removedAt: $removedAt,
        ));
    }

    /**
     * @throws CartAlreadyPurchasedException
     * @throws CartProductNotFoundException
     */
    public function changeQuantity(string $productId, Quantity $quantity, \DateTimeImmutable $changedAt): void
    {
        $this->guardActive();

        $line = $this->lineOf($productId) ?? throw CartProductNotFoundException::forProductId($productId);

        $this->recordThat(new CartProductQuantityChanged(
            id: $this->id,
            lineId: $line->id,
            productId: $productId,
            quantity: $quantity,
            changedAt: $changedAt,
        ));
    }

    public function purchase(\DateTimeImmutable $purchasedAt): void
    {
        if (!$this->canTransitionOperationalTo(CartState::PURCHASED)) {
            return;
        }

        $this->recordThat(new CartPurchased(
            id: $this->id,
            purchasedAt: $purchasedAt,
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

    private function lineOf(string $productId): ?CartLine
    {
        foreach ($this->lines as $line) {
            if ($line->productId === $productId) {
                return $line;
            }
        }

        return null;
    }

    private function canTransitionOperationalTo(CartState $target): bool
    {
        return new StateTransitions(self::OPERATIONAL_TRANSITIONS)->allows($this->operationalState, $target);
    }

    #[Apply]
    private function applyStarted(CartStarted $event): void
    {
        $this->id = $event->id;
        $this->customerId = $event->customerId;
        $this->startedAt = $event->startedAt;
        $this->operationalState = CartState::ACTIVE;
    }

    #[Apply]
    private function applyProductAdded(CartProductAdded $event): void
    {
        $this->lines[$event->lineId->toString()] = new CartLine($event->lineId, $event->productId, $event->quantity, $event->addedAt);
        ++$this->lineSequence;
    }

    #[Apply]
    private function applyProductRemoved(CartProductRemoved $event): void
    {
        unset($this->lines[$event->lineId->toString()]);
    }

    #[Apply]
    private function applyProductQuantityChanged(CartProductQuantityChanged $event): void
    {
        $this->lines[$event->lineId->toString()] = $this->lines[$event->lineId->toString()]->withQuantity($event->quantity, $event->changedAt);
    }

    #[Apply]
    private function applyPurchased(CartPurchased $event): void
    {
        $this->operationalState = CartState::PURCHASED;
        $this->purchasedAt = $event->purchasedAt;
    }
}
