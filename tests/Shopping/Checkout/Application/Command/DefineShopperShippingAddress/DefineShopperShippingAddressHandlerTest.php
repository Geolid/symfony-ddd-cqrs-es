<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Command\DefineShopperShippingAddress;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Mapper\PostalAddressMapper;
use Shopping\Checkout\Application\Command\DefineShopperShippingAddress\DefineShopperShippingAddress;
use Shopping\Checkout\Domain\Exception\ShopperNotFoundException;
use Shopping\Checkout\Domain\Repository\ShopperRepositoryInterface;
use Shopping\Checkout\Domain\ValueObject\ShopperId;
use Shopping\Tests\Checkout\Support\Builder\ShopperBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class DefineShopperShippingAddressHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDefines(): void
    {
        // Given
        $shopper = ShopperBuilder::new()->create();
        $this->store($shopper);
        $shippingAddress = PostalAddressMapper::toArray(ShopperBuilder::sample('shippingAddress'));

        // When
        $this->dispatch(new DefineShopperShippingAddress($shopper->id->toString(), $shippingAddress));

        // Then
        $reloaded = $this->service(ShopperRepositoryInterface::class)->load(ShopperId::fromString($shopper->id->toString()));
        self::assertNotNull($reloaded->shippingAddress);
        self::assertSame($shippingAddress, PostalAddressMapper::toArray($reloaded->shippingAddress));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(ShopperNotFoundException::class);

        // When
        $this->dispatch(new DefineShopperShippingAddress(Uuid::uuid7()->toString(), PostalAddressMapper::toArray(ShopperBuilder::sample('shippingAddress'))));
    }
}
