<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Infrastructure\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Quantity;
use Shared\Tests\Support\TestCase\AbstractIterableFinderTestCase;
use Shopping\Checkout\Application\Finder\CartItem\CartItemFinderInterface;
use Shopping\Checkout\Application\Finder\CartItem\CartItemResult;
use Shopping\Tests\Cart\Support\Factory\CartFactory;
use Symfony\Component\Clock\Clock;

/**
 * @extends AbstractIterableFinderTestCase<CartItemResult>
 */
final class DbalCartItemFinderTest extends AbstractIterableFinderTestCase
{
    #[Test]
    public function itFiltersByCart(): void
    {
        // Given
        $other = CartFactory::new()->productAdded()->create();

        $productId = Uuid::uuid7()->toString();
        $cart = CartFactory::new()->productAdded($productId, $quantity = Quantity::of(2))->create();

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
        $now = Clock::get()->now();

        $carts = [];
        $indexes = [];
        for ($i = 0; $i < $count; ++$i) {
            $firstProductId = Uuid::uuid7()->toString();
            $secondProductId = Uuid::uuid7()->toString();
            $cart = CartFactory::new()
                ->productAdded($firstProductId, addedAt: $now->modify(\sprintf('+%d minutes', $i)))
                ->productAdded($secondProductId, addedAt: $now->modify(\sprintf('+%d minutes', $count + $i)))
                ->create();
            $carts[] = $cart;
            $indexes[] = \sprintf('%s:%s', $cart->id->toString(), $firstProductId);
            $indexes[] = \sprintf('%s:%s', $cart->id->toString(), $secondProductId);
        }

        $this->store(...$carts);

        return $indexes;
    }

    protected function indexOf(object $result): string
    {
        return \sprintf('%s:%s', $result->cartId, $result->productId);
    }
}
