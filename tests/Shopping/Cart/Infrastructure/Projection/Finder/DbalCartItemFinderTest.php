<?php

declare(strict_types=1);

namespace Shopping\Tests\Cart\Infrastructure\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Tests\Support\TestCase\AbstractIterableFinderTestCase;
use Shopping\Cart\Application\Finder\CartItem\CartItemFinderInterface;
use Shopping\Cart\Application\Finder\CartItem\CartItemResult;
use Shopping\Tests\Cart\Support\Builder\CartBuilder;
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
        $other = CartBuilder::new()->productAdded()->create();

        $builder = CartBuilder::new()->productAdded();
        $cart = $builder->create();
        $productAddition = $builder['productAdditions'][0];

        $this->store($other, $cart);

        // When
        $results = iterator_to_array($this->finder()->byCart($cart->id->toString()));

        // Then
        self::assertCount(1, $results);
        self::assertSame($cart->id->toString(), $results[0]->cartId);
        self::assertSame($productAddition['productId'], $results[0]->productId);
        self::assertSame($productAddition['quantity']->value, $results[0]->quantity);
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

        // Each cart gets two products — the second one added later than any other cart's own
        // first product — so grouping by cart_id (both of one cart's items adjacent) diverges
        // from sorting by added_at alone (every cart's first item bunched together, then every
        // second item).
        $carts = [];
        $indexes = [];
        for ($i = 0; $i < $count; ++$i) {
            $firstProductId = Uuid::uuid7()->toString();
            $secondProductId = Uuid::uuid7()->toString();
            $cart = CartBuilder::new()
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
