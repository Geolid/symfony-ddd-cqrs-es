<?php

declare(strict_types=1);

namespace Shopping\Tests\Cart\Domain;

use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Quantity;
use Shared\Tests\Support\Factory\QuantityFactory;
use Shopping\Cart\Domain\Cart;
use Shopping\Cart\Domain\Event\CartProductAdded;
use Shopping\Cart\Domain\Event\CartProductQuantityChanged;
use Shopping\Cart\Domain\Event\CartProductRemoved;
use Shopping\Cart\Domain\Event\CartPurchased;
use Shopping\Cart\Domain\Event\CartStarted;
use Shopping\Cart\Domain\Exception\CartAlreadyPurchasedException;
use Shopping\Cart\Domain\Exception\CartProductNotFoundException;
use Shopping\Cart\Domain\ValueObject\CartId;
use Shopping\Cart\Domain\ValueObject\CartLineId;
use Shopping\Tests\Cart\Support\Factory\CartIdFactory;
use Symfony\Component\Clock\Clock;

final class CartTest extends AggregateRootTestCase
{
    private CartId $id;
    private string $customerId;
    private \DateTimeImmutable $startedAt;
    private string $productId;
    private CartLineId $lineId;
    private Quantity $quantity;
    private Quantity $additionalQuantity;
    private string $otherProductId;
    private CartLineId $otherLineId;
    private \DateTimeImmutable $addedAt;
    private \DateTimeImmutable $removedAt;
    private \DateTimeImmutable $changedAt;
    private \DateTimeImmutable $purchasedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = CartIdFactory::new()->create();
        $this->customerId = Uuid::uuid7()->toString();
        $this->startedAt = Clock::get()->now();
        $this->productId = Uuid::uuid7()->toString();
        $this->lineId = CartLineId::forCart($this->id->toString(), 0);
        $this->quantity = QuantityFactory::new()->create();
        $this->additionalQuantity = Quantity::of($this->quantity->value + 1);
        $this->otherProductId = Uuid::uuid7()->toString();
        $this->otherLineId = CartLineId::forCart($this->id->toString(), 1);
        $this->addedAt = $this->startedAt->modify('+1 minute');
        $this->removedAt = $this->startedAt->modify('+2 minute');
        $this->changedAt = $this->startedAt->modify('+2 minute');
        $this->purchasedAt = $this->startedAt->modify('+3 minute');
    }

    #[Test]
    public function itStarts(): void
    {
        $this
            ->given()
            ->when(fn (): Cart => Cart::start($this->id, $this->customerId, $this->startedAt))
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
    public function itAddsAnotherProductAsNewLine(): void
    {
        $this
            ->given($this->started(), $this->productAdded())
            ->when(fn (Cart $cart) => $cart->addProduct($this->otherProductId, $this->quantity, $this->addedAt))
            ->then($this->otherProductAdded());
    }

    #[Test]
    public function itAddsProductAgainByMergingIntoItsLine(): void
    {
        $this
            ->given($this->started(), $this->productAdded(), $this->otherProductAdded())
            ->when(fn (Cart $cart) => $cart->addProduct($this->productId, $this->additionalQuantity, $this->changedAt))
            ->then(new CartProductQuantityChanged($this->id, $this->lineId, $this->productId, $this->quantity->plus($this->additionalQuantity), $this->changedAt));
    }

    #[Test]
    public function itAddsProductAgainAsNewLineAfterRemoval(): void
    {
        $this
            ->given($this->started(), $this->productAdded(), $this->productRemoved())
            ->when(fn (Cart $cart) => $cart->addProduct($this->productId, $this->quantity, $this->addedAt))
            ->then(new CartProductAdded($this->id, $this->otherLineId, $this->productId, $this->quantity, $this->addedAt));
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
    public function itRemovesTheMatchingLine(): void
    {
        $this
            ->given($this->started(), $this->productAdded(), $this->otherProductAdded())
            ->when(fn (Cart $cart) => $cart->removeProduct($this->otherProductId, $this->removedAt))
            ->then(new CartProductRemoved($this->id, $this->otherLineId, $this->otherProductId, $this->removedAt));
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
    public function itChangesQuantityOfTheMatchingLine(): void
    {
        $this
            ->given($this->started(), $this->productAdded(), $this->otherProductAdded())
            ->when(fn (Cart $cart) => $cart->changeQuantity($this->otherProductId, $this->quantity, $this->changedAt))
            ->then(new CartProductQuantityChanged($this->id, $this->otherLineId, $this->otherProductId, $this->quantity, $this->changedAt));
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
            ->when(fn (Cart $cart) => $cart->purchase($this->purchasedAt))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return Cart::class;
    }

    private function started(): CartStarted
    {
        return new CartStarted($this->id, $this->customerId, $this->startedAt);
    }

    private function productAdded(): CartProductAdded
    {
        return new CartProductAdded($this->id, $this->lineId, $this->productId, $this->quantity, $this->addedAt);
    }

    private function otherProductAdded(): CartProductAdded
    {
        return new CartProductAdded($this->id, $this->otherLineId, $this->otherProductId, $this->quantity, $this->addedAt);
    }

    private function productRemoved(): CartProductRemoved
    {
        return new CartProductRemoved($this->id, $this->lineId, $this->productId, $this->removedAt);
    }

    private function productQuantityChanged(): CartProductQuantityChanged
    {
        return new CartProductQuantityChanged($this->id, $this->lineId, $this->productId, $this->quantity, $this->changedAt);
    }

    private function purchased(): CartPurchased
    {
        return new CartPurchased($this->id, $this->purchasedAt);
    }
}
