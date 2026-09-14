<?php

declare(strict_types=1);

namespace Shopping\Tests\Cart\Infrastructure\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Tests\Support\TestCase\AbstractIterableFinderTestCase;
use Shopping\Cart\Application\Finder\Cart\CartFinderInterface;
use Shopping\Cart\Application\Finder\Cart\CartResult;
use Shopping\Cart\Application\Finder\Cart\Exception\CartResultNotFoundException;
use Shopping\Cart\Domain\Cart;
use Shopping\Tests\Cart\Support\Builder\CartBuilder;

/**
 * @extends AbstractIterableFinderTestCase<CartResult>
 */
final class DbalCartFinderTest extends AbstractIterableFinderTestCase
{
    #[Test]
    public function itGets(): void
    {
        // Given
        $other = CartBuilder::new()->create();
        $builder = CartBuilder::new();
        $cart = $builder->create();
        $this->store($other, $cart);

        // When
        $result = $this->finder()->ofId($cart->id->toString());

        // Then
        self::assertSame($cart->id->toString(), $result->id);
        self::assertSame($builder['customerId'], $result->customerId);
        self::assertSame($builder['startedAt']->format('Y-m-d H:i:s'), $result->startedAt->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Then
        $this->expectException(CartResultNotFoundException::class);

        // When
        $this->finder()->ofId(Uuid::uuid7()->toString());
    }

    #[Test]
    public function itFiltersByProductId(): void
    {
        // Given
        $other = CartBuilder::new()->productAdded()->create();
        $productId = Uuid::uuid7()->toString();
        $cart = CartBuilder::new()->productAdded($productId)->create();
        $this->store($other, $cart);

        // When
        $results = iterator_to_array($this->finder()->byProductId($productId));

        // Then
        self::assertCount(1, $results);
        self::assertSame($cart->id->toString(), $results[0]->id);
    }

    protected function finder(): CartFinderInterface
    {
        return $this->service(CartFinderInterface::class);
    }

    /**
     * @return list<string>
     */
    protected function seed(int $count): array
    {
        $carts = CartBuilder::new()->many($count)->create();
        $this->store(...$carts);

        return array_map(static fn (Cart $cart): string => $cart->id->toString(), $carts);
    }

    protected function indexOf(object $result): string
    {
        return $result->id;
    }
}
