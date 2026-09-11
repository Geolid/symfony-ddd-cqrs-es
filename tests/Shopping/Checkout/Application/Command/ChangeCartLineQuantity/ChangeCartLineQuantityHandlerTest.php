<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Command\ChangeCartLineQuantity;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shopping\Checkout\Application\Command\ChangeCartLineQuantity\ChangeCartLineQuantity;
use Shopping\Checkout\Domain\Cart\Exception\CartNotFoundException;
use Shopping\Checkout\Domain\Cart\Repository\CartRepositoryInterface;
use Shopping\Checkout\Domain\Cart\ValueObject\LineId;
use Shopping\Checkout\Domain\Cart\ValueObject\Quantity;
use Shopping\Tests\Checkout\Support\Builder\CartBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class ChangeCartLineQuantityHandlerTest extends AbstractIntegrationTestCase
{
    private CartRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(CartRepositoryInterface::class);
    }

    #[Test]
    public function itChanges(): void
    {
        // Given
        $builder = CartBuilder::new()->lineAdded(quantity: Quantity::of(1));
        $cart = $builder->create();
        $this->store($cart);
        $lineId = LineId::forProduct($cart->id->toString(), $builder['product']->id);

        // When
        $this->dispatch(new ChangeCartLineQuantity($cart->id->toString(), $lineId->toString(), 5));

        // Then
        $reloaded = $this->repository->load($cart->id);
        $lines = $reloaded->lines();
        self::assertCount(1, $lines);
        self::assertSame(5, $lines[0]->quantity->value);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(CartNotFoundException::class);

        // When
        $this->dispatch(new ChangeCartLineQuantity(Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), 1));
    }
}
