<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Domain\Cart;

use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Domain\Cart\Cart;
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
use Sales\Ordering\Domain\Shared\ValueObject\LineId;
use Sales\Ordering\Domain\Shared\ValueObject\Product;
use Sales\Ordering\Domain\Shared\ValueObject\Quantity;
use Sales\Tests\Ordering\Support\Builder\CartBuilder;
use Shared\Domain\ValueObject\Money;

final class CartTest extends AggregateRootTestCase
{
    private CartId $id;
    private string $buyerId;
    private \DateTimeImmutable $startedAt;
    private Product $product;
    private Quantity $quantity;
    private \DateTimeImmutable $addedAt;
    private \DateTimeImmutable $removedAt;
    private \DateTimeImmutable $changedAt;
    private \DateTimeImmutable $checkedOutAt;
    private \DateTimeImmutable $abandonedAt;
    private \DateTimeImmutable $convertedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = CartId::fromString(Uuid::uuid7()->toString());
        $this->buyerId = CartBuilder::sample('buyerId');
        $this->startedAt = CartBuilder::sample('startedAt');
        $this->product = CartBuilder::sample('product');
        $this->quantity = CartBuilder::sample('quantity');
        $this->addedAt = CartBuilder::sample('addedAt');
        $this->removedAt = CartBuilder::sample('removedAt');
        $this->changedAt = CartBuilder::sample('changedAt');
        $this->checkedOutAt = CartBuilder::sample('checkedOutAt');
        $this->abandonedAt = CartBuilder::sample('abandonedAt');
        $this->convertedAt = CartBuilder::sample('convertedAt');
    }

    #[Test]
    public function itStarts(): void
    {
        $this
            ->given()
            ->when(fn (): Cart => Cart::start($this->id, $this->buyerId, $this->startedAt))
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
    public function itCannotAddLineWhenCheckoutInProgress(): void
    {
        $this
            ->given($this->started(), $this->lineAdded(), $this->checkedOut())
            ->when(fn (Cart $cart) => $cart->addLine($this->product, $this->quantity, $this->addedAt))
            ->expectsException(CartCheckoutInProgressException::class);
    }

    #[Test]
    public function itCannotAddLineWhenConverted(): void
    {
        $this
            ->given($this->started(), $this->lineAdded(), $this->checkedOut(), $this->converted())
            ->when(fn (Cart $cart) => $cart->addLine($this->product, $this->quantity, $this->addedAt))
            ->expectsException(CartAlreadyConvertedException::class);
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
    public function itCannotRemoveLineWhenCheckoutInProgress(): void
    {
        $this
            ->given($this->started(), $this->lineAdded(), $this->checkedOut())
            ->when(fn (Cart $cart) => $cart->removeLine($this->lineId(), $this->removedAt))
            ->expectsException(CartCheckoutInProgressException::class);
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
    public function itCannotChangeLineQuantityWhenCheckoutInProgress(): void
    {
        $this
            ->given($this->started(), $this->lineAdded(), $this->checkedOut())
            ->when(fn (Cart $cart) => $cart->changeQuantity($this->lineId(), $this->quantity, $this->changedAt))
            ->expectsException(CartCheckoutInProgressException::class);
    }

    #[Test]
    public function itChecksOut(): void
    {
        $this
            ->given($this->started(), $this->lineAdded())
            ->when(fn (Cart $cart) => $cart->checkout($this->checkedOutAt))
            ->then($this->checkedOut());
    }

    #[Test]
    public function itDoesNotCheckOutWhenAlreadyInCheckout(): void
    {
        $this
            ->given($this->started(), $this->lineAdded(), $this->checkedOut())
            ->when(static fn (Cart $cart) => $cart->checkout(CartBuilder::sample('checkedOutAt')))
            ->then();
    }

    #[Test]
    public function itCannotCheckOutWhenEmpty(): void
    {
        $this
            ->given($this->started())
            ->when(fn (Cart $cart) => $cart->checkout($this->checkedOutAt))
            ->expectsException(CartEmptyException::class);
    }

    #[Test]
    public function itCannotCheckOutWhenConverted(): void
    {
        $this
            ->given($this->started(), $this->lineAdded(), $this->checkedOut(), $this->converted())
            ->when(fn (Cart $cart) => $cart->checkout($this->checkedOutAt))
            ->expectsException(CartAlreadyConvertedException::class);
    }

    #[Test]
    public function itAbandonsCheckout(): void
    {
        $this
            ->given($this->started(), $this->lineAdded(), $this->checkedOut())
            ->when(fn (Cart $cart) => $cart->abandonCheckout($this->abandonedAt))
            ->then($this->checkoutAbandoned());
    }

    #[Test]
    public function itDoesNotAbandonCheckoutWhenActive(): void
    {
        $this
            ->given($this->started())
            ->when(static fn (Cart $cart) => $cart->abandonCheckout(CartBuilder::sample('abandonedAt')))
            ->then();
    }

    #[Test]
    public function itDoesNotAbandonCheckoutWhenConverted(): void
    {
        $this
            ->given($this->started(), $this->lineAdded(), $this->checkedOut(), $this->converted())
            ->when(static fn (Cart $cart) => $cart->abandonCheckout(CartBuilder::sample('abandonedAt')))
            ->then();
    }

    #[Test]
    public function itConverts(): void
    {
        $this
            ->given($this->started(), $this->lineAdded(), $this->checkedOut())
            ->when(fn (Cart $cart) => $cart->convert($this->convertedAt))
            ->then($this->converted());
    }

    #[Test]
    public function itDoesNotConvertWhenNotInCheckout(): void
    {
        $this
            ->given($this->started())
            ->when(static fn (Cart $cart) => $cart->convert(CartBuilder::sample('convertedAt')))
            ->then();
    }

    #[Test]
    public function itDoesNotConvertWhenAlreadyConverted(): void
    {
        $this
            ->given($this->started(), $this->lineAdded(), $this->checkedOut(), $this->converted())
            ->when(static fn (Cart $cart) => $cart->convert(CartBuilder::sample('convertedAt')))
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
        return new CartStarted($this->id->toString(), $this->buyerId, $this->startedAt);
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

    private function checkedOut(): CartCheckedOut
    {
        return new CartCheckedOut($this->id->toString(), $this->buyerId, $this->totalAmount()->cents, $this->checkedOutAt);
    }

    private function checkoutAbandoned(): CartCheckoutAbandoned
    {
        return new CartCheckoutAbandoned($this->id->toString(), $this->abandonedAt);
    }

    private function converted(): CartConverted
    {
        return new CartConverted($this->id->toString(), $this->convertedAt);
    }

    private function totalAmount(): Money
    {
        return $this->product->price->times($this->quantity->value);
    }
}
