<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Infrastructure\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Sales\Ordering\Application\Finder\CartLine\CartLineFinderInterface;
use Sales\Ordering\Application\Finder\CartLine\CartLineResult;
use Sales\Ordering\Domain\Cart\Cart;
use Sales\Tests\Ordering\Support\Builder\CartBuilder;
use Shared\Tests\Support\TestCase\AbstractIterableFinderTestCase;

/**
 * @extends AbstractIterableFinderTestCase<CartLineResult>
 */
final class DbalCartLineFinderTest extends AbstractIterableFinderTestCase
{
    #[Test]
    public function itFiltersByCart(): void
    {
        // Given
        $other = CartBuilder::new()->lineAdded()->create();
        $builder = CartBuilder::new()->lineAdded();
        $cart = $builder->create();
        $this->store($other, $cart);

        // When
        $results = iterator_to_array($this->finder()->byCart($cart->id->toString()));

        // Then
        self::assertCount(1, $results);
        self::assertSame($builder->lineId()->toString(), $results[0]->lineId);
        self::assertSame($builder['product']->id, $results[0]->productId);
        self::assertSame($builder['product']->label->value, $results[0]->label);
        self::assertSame($builder['product']->price->cents, $results[0]->unitPriceInCents);
        self::assertSame($builder['quantity']->value, $results[0]->quantity);
    }

    protected function finder(): CartLineFinderInterface
    {
        return $this->service(CartLineFinderInterface::class);
    }

    /**
     * @return list<string>
     */
    protected function seed(int $count): array
    {
        $builders = array_map(static fn (): CartBuilder => CartBuilder::new()->lineAdded(), range(1, $count));
        $this->store(...array_map(static fn (CartBuilder $builder): Cart => $builder->create(), $builders));

        return array_map(static fn (CartBuilder $builder): string => $builder->lineId()->toString(), $builders);
    }

    protected function idOf(object $result): string
    {
        return $result->lineId;
    }
}
