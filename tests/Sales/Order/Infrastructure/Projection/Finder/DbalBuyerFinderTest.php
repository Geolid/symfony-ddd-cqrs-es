<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Finder\Buyer\BuyerFinderInterface;
use Sales\Order\Application\Finder\Buyer\BuyerResult;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Shared\Domain\ValueObject\Address;
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
        $otherBuyer = BuyerBuilder::new()->create();
        $this->store($otherBuyer);
        $shippingAddress = $this->shippingAddress();
        $billingAddress = $this->billingAddress();
        $buyer = BuyerBuilder::new()
            ->shippingAddressRegistered($shippingAddress)
            ->billingAddressRegistered($billingAddress)
            ->create();
        $this->store($buyer);

        // When
        $result = $this->finder->ofIdOrNull($buyer->id->toString());

        // Then
        self::assertInstanceOf(BuyerResult::class, $result);
        self::assertSame($buyer->id->toString(), $result->buyerId);
        self::assertNotNull($result->shippingAddress);
        $shippingResult = [
            'recipientName' => $result->shippingAddress->recipientName,
            'street' => $result->shippingAddress->street,
            'postalCode' => $result->shippingAddress->postalCode,
            'city' => $result->shippingAddress->city,
            'countryCode' => $result->shippingAddress->countryCode,
        ];
        $expectedShippingAddress = $shippingAddress->toArray();
        self::assertSame($expectedShippingAddress, $shippingResult);
        self::assertNotNull($result->billingAddress);
        $billingResult = [
            'recipientName' => $result->billingAddress->recipientName,
            'street' => $result->billingAddress->street,
            'postalCode' => $result->billingAddress->postalCode,
            'city' => $result->billingAddress->city,
            'countryCode' => $result->billingAddress->countryCode,
        ];
        $expectedBillingAddress = $billingAddress->toArray();
        self::assertSame($expectedBillingAddress, $billingResult);
    }

    #[Test]
    public function itFindsWithNoAddress(): void
    {
        // Given
        $buyer = BuyerBuilder::new()->create();
        $this->store($buyer);

        // When
        $result = $this->finder->ofIdOrNull($buyer->id->toString());

        // Then
        self::assertInstanceOf(BuyerResult::class, $result);
        self::assertSame($buyer->id->toString(), $result->buyerId);
        self::assertNull($result->shippingAddress);
        self::assertNull($result->billingAddress);
    }

    #[Test]
    public function itFindsWithOnlyShippingAddress(): void
    {
        // Given
        $buyer = BuyerBuilder::new()
            ->shippingAddressRegistered($this->shippingAddress())
            ->create();
        $this->store($buyer);

        // When
        $result = $this->finder->ofIdOrNull($buyer->id->toString());

        // Then
        self::assertInstanceOf(BuyerResult::class, $result);
        self::assertNotNull($result->shippingAddress);
        self::assertNull($result->billingAddress);
    }

    #[Test]
    public function itFindsNoneForUnknownBuyer(): void
    {
        // When
        $result = $this->finder->ofIdOrNull(Uuid::uuid7()->toString());

        // Then
        self::assertNull($result);
    }

    private function shippingAddress(): PostalAddress
    {
        return PostalAddress::of('Ada Lovelace', Address::of('12 rue des Lilas', '75001', 'Paris', 'FR'));
    }

    private function billingAddress(): PostalAddress
    {
        return PostalAddress::of('Ada Lovelace', Address::of('8 avenue Foch', '75116', 'Paris', 'FR'));
    }
}
