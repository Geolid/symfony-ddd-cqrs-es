<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Infrastructure\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shopping\Checkout\Application\Finder\Cart\CartFinderInterface;
use Shopping\Checkout\Application\Finder\Cart\Exception\CartResultNotFoundException;
use Shopping\Checkout\Domain\Cart\ValueObject\LineId;
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
    public function itGets(): void
    {
        // Given
        $other = CartBuilder::new()->lineAdded()->create();
        $product = CartBuilder::sample('product');
        $quantity = CartBuilder::sample('quantity');
        $builder = CartBuilder::new()->lineAdded($product, $quantity);
        $cart = $builder->create();
        $this->store($other, $cart);

        // When
        $result = $this->finder->ofId($cart->id->toString());

        // Then
        self::assertSame($cart->id->toString(), $result->id);
        self::assertSame($builder['shopperId'], $result->shopperId);
        $lineId = LineId::forProduct($cart->id->toString(), $product->id)->toString();
        self::assertSame([[
            'lineId' => $lineId,
            'productId' => $product->id,
            'label' => $product->label->value,
            'unitPriceInCents' => $product->price->cents,
            'quantity' => $quantity->value,
        ]], $result->lines);
        self::assertSame($product->price->cents * $quantity->value, $result->totalAmountInCents);
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
