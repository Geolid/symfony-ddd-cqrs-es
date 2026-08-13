<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Persistence\EventStore\Resolver;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Buyer\BuyerResolverInterface;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Support\AbstractIntegrationTestCase;

final class StreamBuyerResolverTest extends AbstractIntegrationTestCase
{
    private BuyerResolverInterface $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = $this->service(BuyerResolverInterface::class);
    }

    #[Test]
    public function itResolvesTheAddressOfARegisteredCustomer(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->withEmail('buyer@example.com')->store();

        // When
        $buyer = $this->resolver->resolveFor($customer->id()->toString());

        // Then
        self::assertNotNull($buyer);
        self::assertSame($customer->id()->toString(), $buyer->id);
        self::assertSame('buyer@example.com', $buyer->address);
    }

    #[Test]
    public function itResolvesNothingForAnErasedCustomer(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->erased()->store();

        // When
        $buyer = $this->resolver->resolveFor($customer->id()->toString());

        // Then
        self::assertNull($buyer);
    }

    #[Test]
    public function itResolvesNothingForACustomerItNeverSaw(): void
    {
        // When
        $buyer = $this->resolver->resolveFor(Uuid::uuid7()->toString());

        // Then
        self::assertNull($buyer);
    }
}
