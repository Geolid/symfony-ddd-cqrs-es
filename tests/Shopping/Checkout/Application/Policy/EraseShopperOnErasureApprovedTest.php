<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Policy;

use Compliance\Erasing\Application\IntegrationEvent\ErasureApproved\ErasureApprovedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shopping\Checkout\Application\Finder\Shopper\Exception\ShopperResultNotFoundException;
use Shopping\Checkout\Application\Finder\Shopper\ShopperFinderInterface;
use Shopping\Checkout\Application\Policy\EraseShopperOnErasureApproved;
use Shopping\Tests\Checkout\Support\Builder\ShopperBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class EraseShopperOnErasureApprovedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itErases(): void
    {
        // Given
        $builder = ShopperBuilder::new()->erasureRequested();
        $shopper = $builder->create();
        $this->store($shopper);

        // Then
        $this->expectException(ShopperResultNotFoundException::class);

        // When
        $this->trigger(EraseShopperOnErasureApproved::class, new ErasureApprovedIntegrationEvent($builder['identityId'], Clock::get()->now()));
        $this->service(ShopperFinderInterface::class)->ofId($shopper->id->toString());
    }

    #[Test]
    public function itIgnoresWhenNoneExist(): void
    {
        // When
        $this->trigger(EraseShopperOnErasureApproved::class, new ErasureApprovedIntegrationEvent(Uuid::uuid7()->toString(), Clock::get()->now()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
