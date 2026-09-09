<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Cart;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Sales\Ordering\Domain\Cart\Event\CartCheckedOut;
use Sales\Ordering\Domain\Cart\Event\CartCheckoutAbandoned;
use Sales\Ordering\Domain\Cart\Event\CartConverted;
use Sales\Ordering\Domain\Cart\Event\CartLineAdded;
use Sales\Ordering\Domain\Cart\Event\CartLineQuantityChanged;
use Sales\Ordering\Domain\Cart\Event\CartLineRemoved;
use Sales\Ordering\Domain\Cart\Event\CartStarted;
use Sales\Ordering\Domain\Cart\Exception\CartAlreadyConvertedException;
use Sales\Ordering\Domain\Cart\Exception\CartCheckoutInProgressException;
use Sales\Ordering\Domain\Cart\Exception\CartEmptyException;
use Sales\Ordering\Domain\Cart\Exception\CartLineNotFoundException;
use Sales\Ordering\Domain\Cart\ValueObject\CartId;
use Sales\Ordering\Domain\Cart\ValueObject\CartState;
use Sales\Ordering\Domain\Shared\Entity\Line;
use Sales\Ordering\Domain\Shared\ValueObject\LineId;
use Sales\Ordering\Domain\Shared\ValueObject\Product;
use Sales\Ordering\Domain\Shared\ValueObject\Quantity;
use Shared\Domain\Specification\CanTransitionToSpecification;
use Shared\Domain\ValueObject\Money;

#[Aggregate('sales.ordering.cart')]
final class Cart implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    /** @var array<string, list<CartState>> */
    private const array OPERATIONAL_TRANSITIONS = [
        CartState::ACTIVE->value => [CartState::CHECKOUT],
        CartState::CHECKOUT->value => [CartState::ACTIVE, CartState::CONVERTED],
        CartState::CONVERTED->value => [],
    ];

    #[Id]
    public private(set) CartId $id;
    public private(set) string $buyerId;
    private CartState $operationalState;
    /** @var array<string, Line> */
    private array $lines = [];

    public static function start(CartId $id, string $buyerId, \DateTimeImmutable $startedAt): self
    {
        $self = new self();
        $self->recordThat(new CartStarted(
            id: $id->toString(),
            buyerId: $buyerId,
            startedAt: $startedAt,
        ));

        return $self;
    }

    /**
     * @throws CartCheckoutInProgressException
     * @throws CartAlreadyConvertedException
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
     * @throws CartCheckoutInProgressException
     * @throws CartAlreadyConvertedException
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
     * @throws CartCheckoutInProgressException
     * @throws CartAlreadyConvertedException
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

    /**
     * @throws CartAlreadyConvertedException
     * @throws CartEmptyException
     */
    public function checkout(\DateTimeImmutable $now): void
    {
        if ($this->operationalState->isConverted()) {
            throw CartAlreadyConvertedException::forId($this->id);
        }

        if (!$this->canTransitionOperationalTo(CartState::CHECKOUT)) {
            return;
        }

        if ([] === $this->lines) {
            throw CartEmptyException::forId($this->id);
        }

        $this->recordThat(new CartCheckedOut(
            id: $this->id->toString(),
            buyerId: $this->buyerId,
            totalAmountInCents: $this->totalAmountInCents(),
            checkedOutAt: $now,
        ));
    }

    public function abandonCheckout(\DateTimeImmutable $now): void
    {
        if (!$this->canTransitionOperationalTo(CartState::ACTIVE)) {
            return;
        }

        $this->recordThat(new CartCheckoutAbandoned(
            id: $this->id->toString(),
            abandonedAt: $now,
        ));
    }

    public function convert(\DateTimeImmutable $now): void
    {
        if (!$this->canTransitionOperationalTo(CartState::CONVERTED)) {
            return;
        }

        $this->recordThat(new CartConverted(
            id: $this->id->toString(),
            convertedAt: $now,
        ));
    }

    /**
     * @return list<Line>
     */
    public function lines(): array
    {
        return array_values($this->lines);
    }

    public function totalAmountInCents(): int
    {
        $total = array_reduce(
            $this->lines,
            static fn (Money $carry, Line $line): Money => $carry->plus($line->total()),
            Money::fromCents(0),
        );

        return $total->cents;
    }

    /**
     * @throws CartCheckoutInProgressException
     * @throws CartAlreadyConvertedException
     */
    private function guardActive(): void
    {
        if ($this->operationalState->isCheckout()) {
            throw CartCheckoutInProgressException::forId($this->id);
        }

        if ($this->operationalState->isConverted()) {
            throw CartAlreadyConvertedException::forId($this->id);
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
        $this->buyerId = $event->buyerId;
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
    private function applyCheckedOut(CartCheckedOut $event): void
    {
        $this->operationalState = CartState::CHECKOUT;
    }

    #[Apply]
    private function applyCheckoutAbandoned(CartCheckoutAbandoned $event): void
    {
        $this->operationalState = CartState::ACTIVE;
    }

    #[Apply]
    private function applyConverted(CartConverted $event): void
    {
        $this->operationalState = CartState::CONVERTED;
    }
}
