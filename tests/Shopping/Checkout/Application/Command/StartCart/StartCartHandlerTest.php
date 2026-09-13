<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Command\StartCart;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shopping\Checkout\Application\Command\StartCart\Exception\CartAlreadyActiveException;
use Shopping\Checkout\Application\Command\StartCart\StartCart;
use Shopping\Checkout\Application\Finder\Cart\CartFinderInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class StartCartHandlerTest extends AbstractIntegrationTestCase
{
    private CartFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(CartFinderInterface::class);
    }

    #[Test]
    public function itStarts(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $customerId = Uuid::uuid7()->toString();

        // When
        $this->dispatch(new StartCart($id, $customerId));

        // Then
        $result = $this->finder->ofId($id);
        self::assertSame($id, $result->id);
        self::assertSame($customerId, $result->customerId);
    }

    #[Test]
    public function itFailsWhenCustomerAlreadyActive(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $this->dispatch(new StartCart(Uuid::uuid7()->toString(), $customerId));

        // Then
        $this->expectException(CartAlreadyActiveException::class);

        // When
        $this->dispatch(new StartCart(Uuid::uuid7()->toString(), $customerId));
    }
}
