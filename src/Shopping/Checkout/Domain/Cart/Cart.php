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
use Shopping\Checkout\Domain\Cart\Entity\Line;
use Shopping\Checkout\Domain\Cart\Event\CartLineAdded;
use Shopping\Checkout\Domain\Cart\Event\CartLineQuantityChanged;
use Shopping\Checkout\Domain\Cart\Event\CartLineRemoved;
use Shopping\Checkout\Domain\Cart\Event\CartPurchased;
use Shopping\Checkout\Domain\Cart\Event\CartStarted;
use Shopping\Checkout\Domain\Cart\Exception\CartAlreadyPurchasedException;
use Shopping\Checkout\Domain\Cart\Exception\CartLineNotFoundException;
use Shopping\Checkout\Domain\Cart\ValueObject\CartId;
use Shopping\Checkout\Domain\Cart\ValueObject\CartState;
use Shopping\Checkout\Domain\Cart\ValueObject\LineId;
use Shopping\Checkout\Domain\Cart\ValueObject\Product;
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
    /** @var array<string, Line> */
    private array $lines = [];

    public static function start(CartId $id, string $shopperId, \DateTimeImmutable $startedAt): self
    {
        $self = new self();
        $self->recordThat(new CartStarted(
            id: $id->toString(),
            shopperId: $shopperId,
            startedAt: $startedAt,
        ));

        return $self;
    }

    /**
     * @throws CartAlreadyPurchasedException
     */
    public function addLine(Product $product, Quantity $quantity, \DateTimeImmutable $now): void
    {
        $this->guardActive();

        $lineId = LineId::forProduct($this->id->toString(), $product->id);

        $this->recordThat(new CartLineAdded(
            id: $this->id->toString(),
            lineId: $lineId->toString(),
            product: $product,
            quantity: $quantity,
            addedAt: $now,
        ));
    }

    /**
     * @throws CartAlreadyPurchasedException
     * @throws CartLineNotFoundException
     */
    public function removeLine(LineId $lineId, \DateTimeImmutable $now): void
    {
        $this->guardActive();

        if (!isset($this->lines[$lineId->toString()])) {
            throw CartLineNotFoundException::forId($lineId);
        }

        $this->recordThat(new CartLineRemoved(
            id: $this->id->toString(),
            lineId: $lineId->toString(),
            removedAt: $now,
        ));
    }

    /**
     * @throws CartAlreadyPurchasedException
     * @throws CartLineNotFoundException
     */
    public function changeQuantity(LineId $lineId, Quantity $quantity, \DateTimeImmutable $now): void
    {
        $this->guardActive();

        if (!isset($this->lines[$lineId->toString()])) {
            throw CartLineNotFoundException::forId($lineId);
        }

        $this->recordThat(new CartLineQuantityChanged(
            id: $this->id->toString(),
            lineId: $lineId->toString(),
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
            id: $this->id->toString(),
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
        $this->id = CartId::fromString($event->id);
        $this->operationalState = CartState::ACTIVE;
    }

    #[Apply]
    private function applyLineAdded(CartLineAdded $event): void
    {
        $existing = $this->lines[$event->lineId] ?? null;
        $quantity = $existing instanceof Line ? $existing->quantity->plus($event->quantity) : $event->quantity;

        $this->lines[$event->lineId] = new Line(
            LineId::fromString($event->lineId),
            $event->product,
            $quantity,
        );
    }

    #[Apply]
    private function applyLineRemoved(CartLineRemoved $event): void
    {
        unset($this->lines[$event->lineId]);
    }

    #[Apply]
    private function applyLineQuantityChanged(CartLineQuantityChanged $event): void
    {
        $existing = $this->lines[$event->lineId];
        $this->lines[$event->lineId] = new Line($existing->id, $existing->product, $event->quantity);
    }

    #[Apply]
    private function applyPurchased(CartPurchased $event): void
    {
        $this->operationalState = CartState::PURCHASED;
    }
}
