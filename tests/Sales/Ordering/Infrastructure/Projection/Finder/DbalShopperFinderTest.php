<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Infrastructure\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Finder\Shopper\PostalAddressResult;
use Sales\Ordering\Application\Finder\Shopper\ShopperFinderInterface;
use Sales\Ordering\Application\Finder\Shopper\ShopperResult;
use Shared\Application\Mapper\PostalAddressMapper;
use Shopping\Tests\Checkout\Support\Builder\ShopperBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class DbalShopperFinderTest extends AbstractIntegrationTestCase
{
    private ShopperFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(ShopperFinderInterface::class);
    }

    #[Test]
    public function itFindsById(): void
    {
        // Given
        $otherShopper = ShopperBuilder::new()->create();
        $this->store($otherShopper);
        $builder = ShopperBuilder::new()->shippingAddressDefined()->billingAddressDefined();
        $shopper = $builder->create();
        $this->store($shopper);

        // When
        $result = $this->finder->ofIdOrNull($shopper->id->toString());

        // Then
        self::assertInstanceOf(ShopperResult::class, $result);
        self::assertSame($shopper->id->toString(), $result->shopperId);
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
        $shopper = ShopperBuilder::new()->create();
        $this->store($shopper);

        // When
        $result = $this->finder->ofIdOrNull($shopper->id->toString());

        // Then
        self::assertInstanceOf(ShopperResult::class, $result);
        self::assertSame($shopper->id->toString(), $result->shopperId);
        self::assertNull($result->shippingAddress);
        self::assertNull($result->billingAddress);
        self::assertFalse($result->erasureRequested);
    }

    #[Test]
    public function itFindsWithErasureRequested(): void
    {
        // Given
        $shopper = ShopperBuilder::new()->erasureRequested()->create();
        $this->store($shopper);

        // When
        $result = $this->finder->ofIdOrNull($shopper->id->toString());

        // Then
        self::assertInstanceOf(ShopperResult::class, $result);
        self::assertTrue($result->erasureRequested);
    }

    #[Test]
    public function itFindsNoneForUnknownShopper(): void
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
