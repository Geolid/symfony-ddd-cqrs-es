<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Finder\Buyer\BuyerFinderInterface;
use Sales\Order\Application\Finder\Buyer\BuyerResult;
use Sales\Tests\Customer\Support\Builder\CustomerBuilder;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;
use Support\TestCase\AbstractIntegrationTestCase;

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
        $shippingAddress = $this->shippingAddress();
        $billingAddress = $this->billingAddress();
        $customer = CustomerBuilder::new()
            ->shippingAddressRegistered($shippingAddress)
            ->billingAddressRegistered($billingAddress)
            ->create();
        $this->store($customer);

        // When
        $result = $this->finder->ofIdOrNull($customer->id->toString());

        // Then
        self::assertInstanceOf(BuyerResult::class, $result);
        self::assertSame($customer->id->toString(), $result->customerId);
        self::assertNotNull($result->shippingAddress);
        self::assertSame(
            $this->primitiveAddress($shippingAddress),
            [
                'firstName' => $result->shippingAddress->firstName,
                'lastName' => $result->shippingAddress->lastName,
                'street' => $result->shippingAddress->street,
                'postalCode' => $result->shippingAddress->postalCode,
                'city' => $result->shippingAddress->city,
                'countryCode' => $result->shippingAddress->countryCode,
            ],
        );
        self::assertNotNull($result->billingAddress);
        self::assertSame(
            $this->primitiveAddress($billingAddress),
            [
                'firstName' => $result->billingAddress->firstName,
                'lastName' => $result->billingAddress->lastName,
                'street' => $result->billingAddress->street,
                'postalCode' => $result->billingAddress->postalCode,
                'city' => $result->billingAddress->city,
                'countryCode' => $result->billingAddress->countryCode,
            ],
        );
    }

    #[Test]
    public function itFindsWithNoAddress(): void
    {
        // Given
        $customer = CustomerBuilder::new()->create();
        $this->store($customer);

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
        $customer = CustomerBuilder::new()
            ->shippingAddressRegistered($this->shippingAddress())
            ->create();
        $this->store($customer);

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

    private function shippingAddress(): PostalAddress
    {
        return PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris', 'FR'));
    }

    private function billingAddress(): PostalAddress
    {
        return PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('8 avenue Foch', '75116', 'Paris', 'FR'));
    }

    /**
     * @return array{firstName: string, lastName: string, street: string, postalCode: string, city: string, countryCode: string}
     */
    private function primitiveAddress(PostalAddress $address): array
    {
        return [
            'firstName' => $address->fullName->firstName,
            'lastName' => $address->fullName->lastName,
            'street' => $address->address->street,
            'postalCode' => $address->address->postalCode,
            'city' => $address->address->city,
            'countryCode' => $address->address->countryCode->value,
        ];
    }
}
