<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Infrastructure\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Sales\Ordering\Application\Finder\CartLine\CartLineFinderInterface;
use Sales\Ordering\Application\Finder\CartLine\CartLineResult;
use Shared\Tests\Support\TestCase\AbstractIterableFinderTestCase;
use Shopping\Checkout\Domain\Cart\Cart;
use Shopping\Tests\Checkout\Support\Builder\CartBuilder;

/**
 * @extends AbstractIterableFinderTestCase<CartLineResult>
 */
final class DbalCartLineFinderTest extends AbstractIterableFinderTestCase
{
    #[Test]
    public function itFiltersByCart(): void
    {
        // Given
        $other = CartBuilder::new()->lineAdded()->checkedOut()->create();
        $builder = CartBuilder::new()->lineAdded()->checkedOut();
        $cart = $builder->create();
        $this->store($other, $cart);

        // When
        $results = iterator_to_array($this->finder()->byCart($cart->id->toString()));

        // Then
        self::assertCount(1, $results);
        self::assertSame($cart->id->toString(), $results[0]->cartId);
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
        $carts = CartBuilder::new()->lineAdded()->checkedOut()->many($count)->create();
        $this->store(...$carts);

        return array_map(static fn (Cart $cart): string => $cart->lines()[0]->id->toString(), $carts);
    }

    protected function idOf(object $result): string
    {
        return $result->lineId;
    }
}
