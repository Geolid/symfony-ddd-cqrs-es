<?php

declare(strict_types=1);

namespace Shopping\Tests\Cart\Application\Command\ChangeCartProductQuantity;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shopping\Cart\Application\Command\ChangeCartProductQuantity\ChangeCartProductQuantity;
use Shopping\Cart\Application\Finder\CartItem\CartItemFinderInterface;
use Shopping\Cart\Domain\Exception\CartNotFoundException;
use Shopping\Cart\Domain\ValueObject\Quantity;
use Shopping\Tests\Cart\Support\Builder\CartBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class ChangeCartProductQuantityHandlerTest extends AbstractIntegrationTestCase
{
    private CartItemFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(CartItemFinderInterface::class);
    }

    #[Test]
    public function itChanges(): void
    {
        // Given
        $productId = Uuid::uuid7()->toString();
        $cart = CartBuilder::new()->productAdded($productId, Quantity::of(1))->create();
        $this->store($cart);

        // When
        $this->dispatch(new ChangeCartProductQuantity($cart->id->toString(), $productId, 5));

        // Then
        $items = iterator_to_array($this->finder->byCart($cart->id->toString()));
        self::assertCount(1, $items);
        self::assertSame(5, $items[0]->quantity);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(CartNotFoundException::class);

        // When
        $this->dispatch(new ChangeCartProductQuantity(Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), 1));
    }
}
