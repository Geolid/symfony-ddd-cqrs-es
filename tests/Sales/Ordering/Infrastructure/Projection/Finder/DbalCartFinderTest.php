<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Infrastructure\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Finder\Cart\CartFinderInterface;
use Sales\Ordering\Application\Finder\Cart\Exception\CartResultNotFoundException;
use Shopping\Tests\Checkout\Support\Builder\CartBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class DbalCartFinderTest extends AbstractIntegrationTestCase
{
    private CartFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(CartFinderInterface::class);
    }

    #[Test]
    public function itGetsById(): void
    {
        // Given
        $other = CartBuilder::new()->lineAdded()->checkedOut()->create();
        $builder = CartBuilder::new()->lineAdded()->checkedOut();
        $cart = $builder->create();
        $this->store($other, $cart);

        // When
        $result = $this->finder->ofId($cart->id->toString());

        // Then
        self::assertSame($cart->id->toString(), $result->cartId);
        self::assertSame($builder['shopperId'], $result->shopperId);
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Then
        $this->expectException(CartResultNotFoundException::class);

        // When
        $this->finder->ofId(Uuid::uuid7()->toString());
    }
}
