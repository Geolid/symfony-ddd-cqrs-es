<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Domain\Cart;

use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shopping\Checkout\Domain\Cart\Cart;
use Shopping\Checkout\Domain\Cart\Event\CartProductAdded;
use Shopping\Checkout\Domain\Cart\Event\CartProductQuantityChanged;
use Shopping\Checkout\Domain\Cart\Event\CartProductRemoved;
use Shopping\Checkout\Domain\Cart\Event\CartPurchased;
use Shopping\Checkout\Domain\Cart\Event\CartStarted;
use Shopping\Checkout\Domain\Cart\Exception\CartAlreadyPurchasedException;
use Shopping\Checkout\Domain\Cart\Exception\CartProductNotFoundException;
use Shopping\Checkout\Domain\Cart\ValueObject\CartId;
use Shopping\Checkout\Domain\Cart\ValueObject\Quantity;
use Shopping\Tests\Checkout\Support\Builder\CartBuilder;
use Support\SeededFaker;

final class CartTest extends AggregateRootTestCase
{
    private CartId $id;
    private string $shopperId;
    private \DateTimeImmutable $startedAt;
    private string $productId;
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
        $this->productId = Uuid::uuid7()->toString();
        $this->quantity = Quantity::of(SeededFaker::get()->numberBetween(1, 5));
        $this->addedAt = $this->startedAt->modify('+1 minute');
        $this->removedAt = $this->startedAt->modify('+2 minute');
        $this->changedAt = $this->startedAt->modify('+2 minute');
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
    public function itAddsProduct(): void
    {
        $this
            ->given($this->started())
            ->when(fn (Cart $cart) => $cart->addProduct($this->productId, $this->quantity, $this->addedAt))
            ->then($this->productAdded());
    }

    #[Test]
    public function itAddsProductAgainWhenAlreadyPresent(): void
    {
        $this
            ->given($this->started(), $this->productAdded())
            ->when(fn (Cart $cart) => $cart->addProduct($this->productId, $this->quantity, $this->addedAt))
            ->then($this->productAdded());
    }

    #[Test]
    public function itCannotAddProductWhenPurchased(): void
    {
        $this
            ->given($this->started(), $this->productAdded(), $this->purchased())
            ->when(fn (Cart $cart) => $cart->addProduct($this->productId, $this->quantity, $this->addedAt))
            ->expectsException(CartAlreadyPurchasedException::class);
    }

    #[Test]
    public function itRemovesProduct(): void
    {
        $this
            ->given($this->started(), $this->productAdded())
            ->when(fn (Cart $cart) => $cart->removeProduct($this->productId, $this->removedAt))
            ->then($this->productRemoved());
    }

    #[Test]
    public function itCannotRemoveProductWhenNotFound(): void
    {
        $this
            ->given($this->started())
            ->when(fn (Cart $cart) => $cart->removeProduct($this->productId, $this->removedAt))
            ->expectsException(CartProductNotFoundException::class);
    }

    #[Test]
    public function itCannotRemoveProductWhenPurchased(): void
    {
        $this
            ->given($this->started(), $this->productAdded(), $this->purchased())
            ->when(fn (Cart $cart) => $cart->removeProduct($this->productId, $this->removedAt))
            ->expectsException(CartAlreadyPurchasedException::class);
    }

    #[Test]
    public function itChangesQuantity(): void
    {
        $this
            ->given($this->started(), $this->productAdded())
            ->when(fn (Cart $cart) => $cart->changeQuantity($this->productId, $this->quantity, $this->changedAt))
            ->then($this->productQuantityChanged());
    }

    #[Test]
    public function itCannotChangeQuantityWhenNotFound(): void
    {
        $this
            ->given($this->started())
            ->when(fn (Cart $cart) => $cart->changeQuantity($this->productId, $this->quantity, $this->changedAt))
            ->expectsException(CartProductNotFoundException::class);
    }

    #[Test]
    public function itCannotChangeQuantityWhenPurchased(): void
    {
        $this
            ->given($this->started(), $this->productAdded(), $this->purchased())
            ->when(fn (Cart $cart) => $cart->changeQuantity($this->productId, $this->quantity, $this->changedAt))
            ->expectsException(CartAlreadyPurchasedException::class);
    }

    #[Test]
    public function itPurchases(): void
    {
        $this
            ->given($this->started(), $this->productAdded())
            ->when(fn (Cart $cart) => $cart->purchase($this->purchasedAt))
            ->then($this->purchased());
    }

    #[Test]
    public function itDoesNotPurchaseWhenAlreadyPurchased(): void
    {
        $this
            ->given($this->started(), $this->productAdded(), $this->purchased())
            ->when(static fn (Cart $cart) => $cart->purchase(CartBuilder::sample('purchasedAt')))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return Cart::class;
    }

    private function started(): CartStarted
    {
        return new CartStarted($this->id, $this->shopperId, $this->startedAt);
    }

    private function productAdded(): CartProductAdded
    {
        return new CartProductAdded($this->id, $this->productId, $this->quantity, $this->addedAt);
    }

    private function productRemoved(): CartProductRemoved
    {
        return new CartProductRemoved($this->id, $this->productId, $this->removedAt);
    }

    private function productQuantityChanged(): CartProductQuantityChanged
    {
        return new CartProductQuantityChanged($this->id, $this->productId, $this->quantity, $this->changedAt);
    }

    private function purchased(): CartPurchased
    {
        return new CartPurchased($this->id, $this->purchasedAt);
    }
}
