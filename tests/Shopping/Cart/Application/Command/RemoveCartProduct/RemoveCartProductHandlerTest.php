<?php

declare(strict_types=1);

namespace Shopping\Tests\Cart\Application\Command\RemoveCartProduct;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shopping\Cart\Application\Command\RemoveCartProduct\RemoveCartProduct;
use Shopping\Cart\Application\Finder\CartItem\CartItemFinderInterface;
use Shopping\Cart\Domain\Exception\CartNotFoundException;
use Shopping\Tests\Cart\Support\Factory\CartFactory;
use Shopping\Tests\Cart\Support\Factory\CartIdFactory;
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
        $cart = CartFactory::new()->productAdded($productId)->create();
        $this->store($cart);

        // When
        $this->dispatch(new RemoveCartProduct($cart->id->toString(), $productId));

        // Then
        self::assertSame([], iterator_to_array($this->finder->byCart($cart->id->toString())));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = CartIdFactory::new()->create()->toString();

        // Then
        $this->expectException(CartNotFoundException::class);

        // When
        $this->dispatch(new RemoveCartProduct($id, Uuid::uuid7()->toString()));
    }
}
