<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Infrastructure\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Shopping\Checkout\Application\Finder\Cart\CartFinderInterface;
use Shopping\Checkout\Application\Finder\Cart\Exception\CartResultNotFoundException;
use Shopping\Tests\Cart\Support\Factory\CartFactory;
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
    public function itGets(): void
    {
        // Given
        $other = CartFactory::new()->create();
        $cart = CartFactory::new()->create();
        $this->store($other, $cart);

        // When
        $result = $this->finder->ofId($cart->id->toString());

        // Then
        self::assertSame($cart->id->toString(), $result->id);
        self::assertSame($cart->customerId, $result->customerId);
        self::assertSame($cart->startedAt->format('Y-m-d H:i:s'), $result->startedAt->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(CartResultNotFoundException::class);

        // When
        $this->finder->ofId('unknown');
    }
}
