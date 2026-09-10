<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Infrastructure\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Sales\Ordering\Application\Finder\CartLine\CartLineFinderInterface;
use Sales\Tests\Ordering\Support\Builder\CartBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class DbalCartLineFinderTest extends AbstractIntegrationTestCase
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
        $results = iterator_to_array($this->finder()->byCart($cart->id->toString()), false);

        // Then
        self::assertCount(1, $results);
        self::assertSame($builder['product']->id, $results[0]->productId);
        self::assertSame($builder['product']->label->value, $results[0]->label);
        self::assertSame($builder['product']->price->cents, $results[0]->unitPriceInCents);
        self::assertSame($builder['quantity']->value, $results[0]->quantity);
    }

    private function finder(): CartLineFinderInterface
    {
        return $this->service(CartLineFinderInterface::class);
    }
}
