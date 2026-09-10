<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Infrastructure\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\CartStatus;
use Sales\Ordering\Application\Finder\Cart\CartFinderInterface;
use Sales\Ordering\Application\Finder\Cart\Exception\CartResultNotFoundException;
use Sales\Tests\Ordering\Support\Builder\CartBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class DbalCartFinderTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGets(): void
    {
        // Given
        $other = CartBuilder::new()->create();
        $builder = CartBuilder::new()->lineAdded();
        $cart = $builder->create();
        $this->store($other, $cart);

        // When
        $result = $this->finder()->ofId($cart->id->toString());

        // Then
        self::assertSame($cart->id->toString(), $result->id);
        self::assertSame($builder['buyerId'], $result->buyerId);
        self::assertSame(CartStatus::ACTIVE, $result->status);
        self::assertCount(1, $result->lineItems);
        self::assertSame($builder['product']->id, $result->lineItems[0]->productId);
        self::assertSame($builder['product']->label->value, $result->lineItems[0]->label);
        self::assertSame($builder['product']->price->cents, $result->lineItems[0]->unitPriceInCents);
        self::assertSame($builder['quantity']->value, $result->lineItems[0]->quantity);
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Then
        $this->expectException(CartResultNotFoundException::class);

        // When
        $this->finder()->ofId(Uuid::uuid7()->toString());
    }

    private function finder(): CartFinderInterface
    {
        return $this->service(CartFinderInterface::class);
    }
}
