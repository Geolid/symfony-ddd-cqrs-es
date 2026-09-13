<?php

declare(strict_types=1);

namespace Shopping\Tests\Cart\Application\Command\PurchaseCart;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Shopping\Cart\Application\CartUniqueKey;
use Shopping\Cart\Application\Command\PurchaseCart\PurchaseCart;
use Shopping\Cart\Domain\Event\CartPurchased;
use Shopping\Cart\Domain\Exception\CartNotFoundException;
use Shopping\Tests\Cart\Support\Builder\CartBuilder;
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
        $cartBuilder = CartBuilder::new()->productAdded();
        $cart = $cartBuilder->create();
        $this->store($cart);
        $customerKey = UniqueKey::for(CartUniqueKey::CUSTOMER);
        $this->uniqueValues->claim($customerKey, $cartBuilder['customerId'], $cart->id->toString());

        // When
        $this->dispatch(new PurchaseCart($cart->id->toString()));

        // Then
        $event = $this->publishedEventOf(CartPurchased::class);
        self::assertSame($cart->id->toString(), $event->id->toString());
        self::assertFalse($this->uniqueValues->isClaimed($customerKey, $cartBuilder['customerId']));
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
