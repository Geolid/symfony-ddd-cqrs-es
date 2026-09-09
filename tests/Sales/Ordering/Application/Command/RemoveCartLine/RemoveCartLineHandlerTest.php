<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Command\RemoveCartLine;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Command\RemoveCartLine\RemoveCartLine;
use Sales\Ordering\Domain\Cart\Exception\CartNotFoundException;
use Sales\Ordering\Domain\Cart\Repository\CartRepositoryInterface;
use Sales\Ordering\Domain\Shared\ValueObject\LineId;
use Sales\Tests\Ordering\Support\Builder\CartBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class RemoveCartLineHandlerTest extends AbstractIntegrationTestCase
{
    private CartRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(CartRepositoryInterface::class);
    }

    #[Test]
    public function itRemoves(): void
    {
        // Given
        $builder = CartBuilder::new()->lineAdded();
        $cart = $builder->create();
        $this->store($cart);
        $lineId = LineId::forProduct($cart->id->toString(), $builder['product']->id);

        // When
        $this->dispatch(new RemoveCartLine($cart->id->toString(), $lineId->toString()));

        // Then
        $reloaded = $this->repository->load($cart->id);
        self::assertSame([], $reloaded->lines());
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(CartNotFoundException::class);

        // When
        $this->dispatch(new RemoveCartLine(Uuid::uuid7()->toString(), Uuid::uuid7()->toString()));
    }
}
