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
        $buyer = BuyerBuilder::new()
            ->shippingAddressRegistered($shippingAddress)
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
}
