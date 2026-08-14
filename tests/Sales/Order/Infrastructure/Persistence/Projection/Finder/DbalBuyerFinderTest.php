<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Persistence\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Customer\Domain\Repository\CustomerAddressesRepositoryInterface;
use Sales\Order\Application\Finder\Buyer\BuyerFinderInterface;
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
    public function itFindsABuyerWithBothAddressesCompleted(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->store();
        $customerAddresses = $this->service(CustomerAddressesRepositoryInterface::class)->load($customer->id());
        $customerAddresses->setShippingAddress(
            PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris')),
            new \DateTimeImmutable('now +00:00'),
        );
        $customerAddresses->setBillingAddress(
            PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('8 avenue Foch', '75116', 'Paris')),
            new \DateTimeImmutable('now +00:00'),
        );
        $this->store($customerAddresses);

        // When
        $result = $this->finder->ofId($customer->id()->toString());

        // Then
        self::assertSame($customer->id()->toString(), $result->customerId);
        self::assertSame(
            ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => 'Paris'],
            $result->shippingAddress,
        );
        self::assertSame(
            ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '8 avenue Foch', 'postalCode' => '75116', 'city' => 'Paris'],
            $result->billingAddress,
        );
    }

    #[Test]
    public function itFindsABuyerWithNoAddressCompleted(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->store();

        // When
        $result = $this->finder->ofId($customer->id()->toString());

        // Then
        self::assertSame($customer->id()->toString(), $result->customerId);
        self::assertNull($result->shippingAddress);
        self::assertNull($result->billingAddress);
    }

    #[Test]
    public function itFindsABuyerWithOnlyTheShippingAddressCompleted(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->store();
        $customerAddresses = $this->service(CustomerAddressesRepositoryInterface::class)->load($customer->id());
        $customerAddresses->setShippingAddress(
            PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris')),
            new \DateTimeImmutable('now +00:00'),
        );
        $this->store($customerAddresses);

        // When
        $result = $this->finder->ofId($customer->id()->toString());

        // Then
        self::assertNotNull($result->shippingAddress);
        self::assertNull($result->billingAddress);
    }

    #[Test]
    public function itFindsNoBuyerForAnUnknownCustomer(): void
    {
        // When
        $result = $this->finder->ofId(Uuid::uuid7()->toString());

        // Then
        self::assertNull($result);
    }
}
