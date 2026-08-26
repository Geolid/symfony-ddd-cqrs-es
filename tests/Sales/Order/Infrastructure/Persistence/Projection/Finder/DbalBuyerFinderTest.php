<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Persistence\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Finder\Buyer\BuyerFinderInterface;
use Sales\Order\Application\Finder\Buyer\BuyerResult;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;
use Support\AbstractIntegrationTestCase;

final class DbalBuyerFinderTest extends AbstractIntegrationTestCase
{
    private BuyerFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(BuyerFinderInterface::class);
    }

    #[Test]
    public function itFindsById(): void
    {
        // Given
        $customer = CustomerTestFactory::new()
            ->withShippingAddress(PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris')))
            ->withBillingAddress(PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('8 avenue Foch', '75116', 'Paris')))
            ->store();

        // When
        $result = $this->finder->ofIdOrNull($customer->id->toString());

        // Then
        self::assertInstanceOf(BuyerResult::class, $result);
        self::assertSame($customer->id->toString(), $result->customerId);
        self::assertNotNull($result->shippingAddress);
        self::assertSame(
            ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => 'Paris'],
            [
                'firstName' => $result->shippingAddress->firstName,
                'lastName' => $result->shippingAddress->lastName,
                'street' => $result->shippingAddress->street,
                'postalCode' => $result->shippingAddress->postalCode,
                'city' => $result->shippingAddress->city,
            ],
        );
        self::assertNotNull($result->billingAddress);
        self::assertSame(
            ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '8 avenue Foch', 'postalCode' => '75116', 'city' => 'Paris'],
            [
                'firstName' => $result->billingAddress->firstName,
                'lastName' => $result->billingAddress->lastName,
                'street' => $result->billingAddress->street,
                'postalCode' => $result->billingAddress->postalCode,
                'city' => $result->billingAddress->city,
            ],
        );
    }

    #[Test]
    public function itFindsWithNoAddress(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->store();

        // When
        $result = $this->finder->ofIdOrNull($customer->id->toString());

        // Then
        self::assertInstanceOf(BuyerResult::class, $result);
        self::assertSame($customer->id->toString(), $result->customerId);
        self::assertNull($result->shippingAddress);
        self::assertNull($result->billingAddress);
    }

    #[Test]
    public function itFindsWithOnlyShippingAddress(): void
    {
        // Given
        $customer = CustomerTestFactory::new()
            ->withShippingAddress(PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris')))
            ->store();

        // When
        $result = $this->finder->ofIdOrNull($customer->id->toString());

        // Then
        self::assertInstanceOf(BuyerResult::class, $result);
        self::assertNotNull($result->shippingAddress);
        self::assertNull($result->billingAddress);
    }

    #[Test]
    public function itFindsNoneForUnknownCustomer(): void
    {
        // When
        $result = $this->finder->ofIdOrNull(Uuid::uuid7()->toString());

        // Then
        self::assertNull($result);
    }
}
