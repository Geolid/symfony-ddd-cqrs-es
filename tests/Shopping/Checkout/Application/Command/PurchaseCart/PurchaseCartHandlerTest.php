<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Command\PurchaseCart;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Shopping\Checkout\Application\CartUniqueKey;
use Shopping\Checkout\Application\Command\PurchaseCart\PurchaseCart;
use Shopping\Checkout\Domain\Cart\Event\CartPurchased;
use Shopping\Checkout\Domain\Cart\Exception\CartNotFoundException;
use Shopping\Tests\Checkout\Support\Builder\CartBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class PurchaseCartHandlerTest extends AbstractIntegrationTestCase
{
    private UniquenessRegistryInterface $uniqueValues;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uniqueValues = $this->service(UniquenessRegistryInterface::class);
    }

    #[Test]
    public function itPurchases(): void
    {
        // Given
        $cartBuilder = CartBuilder::new()->lineAdded();
        $cart = $cartBuilder->create();
        $this->store($cart);
        $shopperKey = UniqueKey::for(CartUniqueKey::SHOPPER);
        $this->uniqueValues->claim($shopperKey, $cartBuilder['shopperId'], $cart->id->toString());

        // When
        $this->dispatch(new PurchaseCart($cart->id->toString()));

        // Then
        $event = $this->publishedEventOf(CartPurchased::class);
        self::assertSame($cart->id->toString(), $event->id);
        self::assertFalse($this->uniqueValues->isClaimed($shopperKey, $cartBuilder['shopperId']));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(CartNotFoundException::class);

        // When
        $this->dispatch(new PurchaseCart(Uuid::uuid7()->toString()));
    }
}
