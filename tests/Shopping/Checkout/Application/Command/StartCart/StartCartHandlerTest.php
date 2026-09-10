<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Command\StartCart;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shopping\Checkout\Application\Command\StartCart\Exception\CartAlreadyActiveException;
use Shopping\Checkout\Application\Command\StartCart\StartCart;
use Shopping\Checkout\Domain\Cart\Repository\CartRepositoryInterface;
use Shopping\Checkout\Domain\Cart\ValueObject\CartId;
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
        $shopperId = Uuid::uuid7()->toString();

        // When
        $this->dispatch(new StartCart($id, $shopperId));

        // Then
        $cart = $this->repository->load(CartId::fromString($id));
        self::assertSame($id, $cart->id->toString());
        self::assertSame($shopperId, $cart->shopperId);
    }

    #[Test]
    public function itFailsWhenShopperAlreadyActive(): void
    {
        // Given
        $shopperId = Uuid::uuid7()->toString();
        $this->dispatch(new StartCart(Uuid::uuid7()->toString(), $shopperId));

        // Then
        $this->expectException(CartAlreadyActiveException::class);

        // When
        $this->dispatch(new StartCart(Uuid::uuid7()->toString(), $shopperId));
    }
}
