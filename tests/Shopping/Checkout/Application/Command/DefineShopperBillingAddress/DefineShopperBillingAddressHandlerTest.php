<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Command\DefineShopperBillingAddress;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Mapper\PostalAddressMapper;
use Shopping\Checkout\Application\Command\DefineShopperBillingAddress\DefineShopperBillingAddress;
use Shopping\Checkout\Domain\Exception\ShopperNotFoundException;
use Shopping\Checkout\Domain\Repository\ShopperRepositoryInterface;
use Shopping\Checkout\Domain\ValueObject\ShopperId;
use Shopping\Tests\Checkout\Support\Builder\ShopperBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class DefineShopperBillingAddressHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDefines(): void
    {
        // Given
        $shopper = ShopperBuilder::new()->create();
        $this->store($shopper);
        $billingAddress = PostalAddressMapper::toArray(ShopperBuilder::sample('billingAddress'));

        // When
        $this->dispatch(new DefineShopperBillingAddress($shopper->id->toString(), $billingAddress));

        // Then
        $reloaded = $this->service(ShopperRepositoryInterface::class)->load(ShopperId::fromString($shopper->id->toString()));
        self::assertNotNull($reloaded->billingAddress);
        self::assertSame($billingAddress, PostalAddressMapper::toArray($reloaded->billingAddress));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(ShopperNotFoundException::class);

        // When
        $this->dispatch(new DefineShopperBillingAddress(Uuid::uuid7()->toString(), PostalAddressMapper::toArray(ShopperBuilder::sample('billingAddress'))));
    }
}
