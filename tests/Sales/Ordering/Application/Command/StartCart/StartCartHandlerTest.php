<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Command\StartCart;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\CartStatus;
use Sales\Ordering\Application\Command\StartCart\StartCart;
use Sales\Ordering\Application\Finder\Cart\CartFinderInterface;
use Sales\Ordering\Application\Uniqueness\Exception\CartAlreadyActiveException;
use Support\TestCase\AbstractIntegrationTestCase;

final class StartCartHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itStarts(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $buyerId = Uuid::uuid7()->toString();

        // When
        $this->dispatch(new StartCart($id, $buyerId));

        // Then
        $result = $this->service(CartFinderInterface::class)->ofId($id);
        self::assertSame($id, $result->id);
        self::assertSame($buyerId, $result->buyerId);
        self::assertSame(CartStatus::ACTIVE, $result->status);
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
