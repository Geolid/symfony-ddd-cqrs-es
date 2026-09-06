<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Finder\Buyer\BuyerFinderInterface;
use Sales\Order\Application\Finder\Buyer\BuyerResult;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
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
        $builder = BuyerBuilder::new()->postalAddressDefined();
        $buyer = $builder->create();
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
        self::assertSame($builder['postalAddress']->toArray(), $shippingResult);
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
}
