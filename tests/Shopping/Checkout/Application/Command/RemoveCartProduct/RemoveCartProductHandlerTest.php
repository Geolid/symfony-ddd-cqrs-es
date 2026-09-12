<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Command\RemoveCartProduct;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shopping\Checkout\Application\Command\RemoveCartProduct\RemoveCartProduct;
use Shopping\Checkout\Application\Finder\CartItem\CartItemFinderInterface;
use Shopping\Checkout\Domain\Cart\Exception\CartNotFoundException;
use Shopping\Tests\Checkout\Support\Builder\CartBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class RemoveCartProductHandlerTest extends AbstractIntegrationTestCase
{
    private CartItemFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(CartItemFinderInterface::class);
    }

    #[Test]
    public function itRemoves(): void
    {
        // Given
        $productId = Uuid::uuid7()->toString();
        $cart = CartBuilder::new()->productAdded($productId)->create();
        $this->store($cart);

        // When
        $this->dispatch(new RemoveCartProduct($cart->id->toString(), $productId));

        // Then
        self::assertSame([], iterator_to_array($this->finder->byCart($cart->id->toString())));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(CartNotFoundException::class);

        // When
        $this->dispatch(new RemoveCartProduct(Uuid::uuid7()->toString(), Uuid::uuid7()->toString()));
    }
}
