<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Command\ChangeCartLineQuantity;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Command\ChangeCartLineQuantity\ChangeCartLineQuantity;
use Sales\Ordering\Domain\Cart\Exception\CartNotFoundException;
use Sales\Ordering\Domain\Cart\Repository\CartRepositoryInterface;
use Sales\Ordering\Domain\Shared\ValueObject\LineId;
use Sales\Tests\Ordering\Support\Builder\CartBuilder;
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
        $builder = CartBuilder::new()->lineAdded();
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
