<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Domain\Cart;

use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shopping\Checkout\Domain\Cart\Cart;
use Shopping\Checkout\Domain\Cart\Event\CartLineAdded;
use Shopping\Checkout\Domain\Cart\Event\CartLineQuantityChanged;
use Shopping\Checkout\Domain\Cart\Event\CartLineRemoved;
use Shopping\Checkout\Domain\Cart\Event\CartPurchased;
use Shopping\Checkout\Domain\Cart\Event\CartStarted;
use Shopping\Checkout\Domain\Cart\Exception\CartAlreadyPurchasedException;
use Shopping\Checkout\Domain\Cart\Exception\CartLineNotFoundException;
use Shopping\Checkout\Domain\Cart\ValueObject\CartId;
use Shopping\Checkout\Domain\Cart\ValueObject\LineId;
use Shopping\Checkout\Domain\Cart\ValueObject\Product;
use Shopping\Checkout\Domain\Cart\ValueObject\Quantity;
use Shopping\Tests\Checkout\Support\Builder\CartBuilder;

final class CartTest extends AggregateRootTestCase
{
    private CartId $id;
    private string $shopperId;
    private \DateTimeImmutable $startedAt;
    private Product $product;
    private Quantity $quantity;
    private \DateTimeImmutable $addedAt;
    private \DateTimeImmutable $removedAt;
    private \DateTimeImmutable $changedAt;
    private \DateTimeImmutable $purchasedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = CartId::fromString(Uuid::uuid7()->toString());
        $this->shopperId = CartBuilder::sample('shopperId');
        $this->startedAt = CartBuilder::sample('startedAt');
        $this->product = CartBuilder::sample('product');
        $this->quantity = CartBuilder::sample('quantity');
        $this->addedAt = CartBuilder::sample('addedAt');
        $this->removedAt = CartBuilder::sample('removedAt');
        $this->changedAt = CartBuilder::sample('changedAt');
        $this->purchasedAt = CartBuilder::sample('purchasedAt');
    }

    #[Test]
    public function itStarts(): void
    {
        $this
            ->given()
            ->when(fn (): Cart => Cart::start($this->id, $this->shopperId, $this->startedAt))
            ->then($this->started());
    }

    #[Test]
    public function itAddsLine(): void
    {
        $this
            ->given($this->started())
            ->when(fn (Cart $cart) => $cart->addLine($this->product, $this->quantity, $this->addedAt))
            ->then($this->lineAdded());
    }

    #[Test]
    public function itCannotAddLineWhenPurchased(): void
    {
        $this
            ->given($this->started(), $this->lineAdded(), $this->purchased())
            ->when(fn (Cart $cart) => $cart->addLine($this->product, $this->quantity, $this->addedAt))
            ->expectsException(CartAlreadyPurchasedException::class);
    }

    #[Test]
    public function itRemovesLine(): void
    {
        $this
            ->given($this->started(), $this->lineAdded())
            ->when(fn (Cart $cart) => $cart->removeLine($this->lineId(), $this->removedAt))
            ->then($this->lineRemoved());
    }

    #[Test]
    public function itCannotRemoveLineWhenNotFound(): void
    {
        $this
            ->given($this->started())
            ->when(fn (Cart $cart) => $cart->removeLine($this->lineId(), $this->removedAt))
            ->expectsException(CartLineNotFoundException::class);
    }

    #[Test]
    public function itCannotRemoveLineWhenPurchased(): void
    {
        $this
            ->given($this->started(), $this->lineAdded(), $this->purchased())
            ->when(fn (Cart $cart) => $cart->removeLine($this->lineId(), $this->removedAt))
            ->expectsException(CartAlreadyPurchasedException::class);
    }

    #[Test]
    public function itChangesLineQuantity(): void
    {
        $this
            ->given($this->started(), $this->lineAdded())
            ->when(fn (Cart $cart) => $cart->changeQuantity($this->lineId(), $this->quantity, $this->changedAt))
            ->then($this->lineQuantityChanged());
    }

    #[Test]
    public function itCannotChangeLineQuantityWhenNotFound(): void
    {
        $this
            ->given($this->started())
            ->when(fn (Cart $cart) => $cart->changeQuantity($this->lineId(), $this->quantity, $this->changedAt))
            ->expectsException(CartLineNotFoundException::class);
    }

    #[Test]
    public function itCannotChangeLineQuantityWhenPurchased(): void
    {
        $this
            ->given($this->started(), $this->lineAdded(), $this->purchased())
            ->when(fn (Cart $cart) => $cart->changeQuantity($this->lineId(), $this->quantity, $this->changedAt))
            ->expectsException(CartAlreadyPurchasedException::class);
    }

    #[Test]
    public function itPurchases(): void
    {
        $this
            ->given($this->started(), $this->lineAdded())
            ->when(fn (Cart $cart) => $cart->purchase($this->purchasedAt))
            ->then($this->purchased());
    }

    #[Test]
    public function itDoesNotPurchaseWhenAlreadyPurchased(): void
    {
        $this
            ->given($this->started(), $this->lineAdded(), $this->purchased())
            ->when(static fn (Cart $cart) => $cart->purchase(CartBuilder::sample('purchasedAt')))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return Cart::class;
    }

    private function lineId(): LineId
    {
        return LineId::forProduct($this->id->toString(), $this->product->id);
    }

    private function started(): CartStarted
    {
        return new CartStarted($this->id->toString(), $this->shopperId, $this->startedAt);
    }

    private function lineAdded(): CartLineAdded
    {
        return new CartLineAdded($this->id->toString(), $this->lineId()->toString(), $this->product, $this->quantity, $this->addedAt);
    }

    private function lineRemoved(): CartLineRemoved
    {
        return new CartLineRemoved($this->id->toString(), $this->lineId()->toString(), $this->removedAt);
    }

    private function lineQuantityChanged(): CartLineQuantityChanged
    {
        return new CartLineQuantityChanged($this->id->toString(), $this->lineId()->toString(), $this->quantity, $this->changedAt);
    }

    private function purchased(): CartPurchased
    {
        return new CartPurchased($this->id->toString(), $this->purchasedAt);
    }
}
