<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Policy;

use Compliance\Erasing\Application\IntegrationEvent\ErasureCancelled\ErasureCancelledIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\ErasureStatus;
use Shopping\Checkout\Application\Finder\Shopper\ShopperFinderInterface;
use Shopping\Checkout\Application\Policy\CancelShopperErasureOnErasureCancelled;
use Shopping\Tests\Checkout\Support\Builder\ShopperBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class CancelShopperErasureOnErasureCancelledTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCancels(): void
    {
        // Given
        $builder = ShopperBuilder::new()->erasureRequested();
        $shopper = $builder->create();
        $this->store($shopper);

        // When
        $this->trigger(CancelShopperErasureOnErasureCancelled::class, new ErasureCancelledIntegrationEvent($builder['identityId'], Clock::get()->now()));

        // Then
        $result = $this->service(ShopperFinderInterface::class)->ofId($shopper->id->toString());
        self::assertSame(ErasureStatus::RETAINED, $result->erasureStatus);
    }

    #[Test]
    public function itIgnoresWhenNoneExist(): void
    {
        // When
        $this->trigger(CancelShopperErasureOnErasureCancelled::class, new ErasureCancelledIntegrationEvent(Uuid::uuid7()->toString(), Clock::get()->now()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
