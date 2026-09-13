<?php

declare(strict_types=1);

namespace Shopping\Tests\Cart\Infrastructure\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Tests\Support\TestCase\AbstractIterableFinderTestCase;
use Shopping\Cart\Application\Finder\CartItem\CartItemFinderInterface;
use Shopping\Cart\Application\Finder\CartItem\CartItemResult;
use Shopping\Cart\Domain\ValueObject\Quantity;
use Shopping\Tests\Cart\Support\Builder\CartBuilder;

/**
 * @extends AbstractIterableFinderTestCase<CartItemResult>
 */
final class DbalCartItemFinderTest extends AbstractIterableFinderTestCase
{
    #[Test]
    public function itFiltersByCart(): void
    {
        // Given
        $other = CartBuilder::new()->productAdded()->create();

        $productId = Uuid::uuid7()->toString();
        $cart = CartBuilder::new()->productAdded($productId, $quantity = Quantity::of(3))->create();

        $this->store($other, $cart);

        // When
        $results = iterator_to_array($this->finder()->byCart($cart->id->toString()));

        // Then
        self::assertCount(1, $results);
        self::assertSame($cart->id->toString(), $results[0]->cartId);
        self::assertSame($productId, $results[0]->productId);
        self::assertSame($quantity->value, $results[0]->quantity);
    }

    protected function finder(): CartItemFinderInterface
    {
        return $this->service(CartItemFinderInterface::class);
    }

    /**
     * @return list<string>
     */
    protected function seed(int $count): array
    {
        $productIds = [];
        $carts = [];
        for ($i = 0; $i < $count; ++$i) {
            $productIds[] = $productId = Uuid::uuid7()->toString();
            $carts[] = CartBuilder::new()->productAdded($productId)->create();
        }

        $this->store(...$carts);

        return $productIds;
    }

    protected function idOf(object $result): string
    {
        return $result->productId;
    }
}
