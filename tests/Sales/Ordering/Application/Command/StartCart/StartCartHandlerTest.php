<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Command\StartCart;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Command\StartCart\StartCart;
use Sales\Ordering\Application\Uniqueness\Exception\CartAlreadyActiveException;
use Sales\Ordering\Domain\Cart\Repository\CartRepositoryInterface;
use Sales\Ordering\Domain\Cart\ValueObject\CartId;
use Support\TestCase\AbstractIntegrationTestCase;

final class StartCartHandlerTest extends AbstractIntegrationTestCase
{
    private CartRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(CartRepositoryInterface::class);
    }

    #[Test]
    public function itStarts(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $buyerId = Uuid::uuid7()->toString();

        // When
        $this->dispatch(new StartCart($id, $buyerId));

        // Then
        $cart = $this->repository->load(CartId::fromString($id));
        self::assertSame($id, $cart->id->toString());
        self::assertSame($buyerId, $cart->buyerId);
    }

    #[Test]
    public function itFailsWhenBuyerAlreadyActive(): void
    {
        // Given
        $buyerId = Uuid::uuid7()->toString();
        $this->dispatch(new StartCart(Uuid::uuid7()->toString(), $buyerId));

        // Then
        $this->expectException(CartAlreadyActiveException::class);

        // When
        $this->dispatch(new StartCart(Uuid::uuid7()->toString(), $buyerId));
    }
}
