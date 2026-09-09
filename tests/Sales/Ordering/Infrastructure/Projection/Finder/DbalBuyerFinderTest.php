<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Infrastructure\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Finder\Buyer\BuyerFinderInterface;
use Sales\Ordering\Application\Finder\Buyer\BuyerResult;
use Sales\Ordering\Application\Finder\Buyer\PostalAddressResult;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Shared\Application\Mapper\PostalAddressMapper;
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
        $builder = BuyerBuilder::new()->shippingAddressDefined()->billingAddressDefined();
        $buyer = $builder->create();
        $this->store($buyer);

        // When
        $result = $this->finder->ofIdOrNull($buyer->id->toString());

        // Then
        self::assertInstanceOf(BuyerResult::class, $result);
        self::assertSame($buyer->id->toString(), $result->buyerId);
        self::assertNotNull($result->shippingAddress);
        self::assertSame(PostalAddressMapper::toArray($builder['shippingAddress']), $this->toArray($result->shippingAddress));
        self::assertNotNull($result->billingAddress);
        self::assertSame(PostalAddressMapper::toArray($builder['billingAddress']), $this->toArray($result->billingAddress));
        self::assertFalse($result->erasureRequested);
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
        self::assertFalse($result->erasureRequested);
    }

    #[Test]
    public function itFindsWithErasureRequested(): void
    {
        // Given
        $buyer = BuyerBuilder::new()->erasureRequested()->create();
        $this->store($buyer);

        // When
        $result = $this->finder->ofIdOrNull($buyer->id->toString());

        // Then
        self::assertInstanceOf(BuyerResult::class, $result);
        self::assertTrue($result->erasureRequested);
    }

    #[Test]
    public function itFindsNoneForUnknownBuyer(): void
    {
        // When
        $result = $this->finder->ofIdOrNull(Uuid::uuid7()->toString());

        // Then
        self::assertNull($result);
    }

    /**
     * @return array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}}
     */
    private function toArray(PostalAddressResult $postalAddressResult): array
    {
        return [
            'recipientName' => $postalAddressResult->recipientName,
            'address' => [
                'street' => $postalAddressResult->address->street,
                'postalCode' => $postalAddressResult->address->postalCode,
                'city' => $postalAddressResult->address->city,
                'countryCode' => $postalAddressResult->address->countryCode,
            ],
        ];
    }
}
