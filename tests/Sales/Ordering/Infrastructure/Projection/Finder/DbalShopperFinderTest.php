<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Infrastructure\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Finder\Shopper\PostalAddressResult;
use Sales\Ordering\Application\Finder\Shopper\ShopperFinderInterface;
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
    public function itFinds(): void
    {
        // Given
        $other = ShopperBuilder::new()->create();
        $builder = ShopperBuilder::new()->shippingAddressDefined()->billingAddressDefined();
        $shopper = $builder->create();
        $this->store($other, $shopper);

        // When
        $found = $this->finder->ofIdOrNull($shopper->id->toString());
        $notFound = $this->finder->ofIdOrNull(Uuid::uuid7()->toString());

        // Then
        self::assertNotNull($found);
        self::assertSame($shopper->id->toString(), $found->shopperId);
        self::assertNotNull($found->shippingAddress);
        self::assertSame(PostalAddressMapper::toArray($builder['shippingAddress']), $this->toArray($found->shippingAddress));
        self::assertNotNull($found->billingAddress);
        self::assertSame(PostalAddressMapper::toArray($builder['billingAddress']), $this->toArray($found->billingAddress));
        self::assertFalse($found->erasureRequested);
        self::assertNull($notFound);
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
        self::assertNotNull($result);
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
        self::assertNotNull($result);
        self::assertTrue($result->erasureRequested);
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
