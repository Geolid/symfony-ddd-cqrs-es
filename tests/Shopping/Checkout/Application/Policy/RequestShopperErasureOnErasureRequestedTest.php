<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Policy;

use Compliance\Erasing\Application\IntegrationEvent\ErasureRequested\ErasureRequestedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\ErasureStatus;
use Shopping\Checkout\Application\Finder\Shopper\ShopperFinderInterface;
use Shopping\Checkout\Application\Policy\RequestShopperErasureOnErasureRequested;
use Shopping\Tests\Checkout\Support\Builder\ShopperBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class RequestShopperErasureOnErasureRequestedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRequests(): void
    {
        // Given
        $builder = ShopperBuilder::new();
        $shopper = $builder->create();
        $this->store($shopper);

        // When
        $this->trigger(RequestShopperErasureOnErasureRequested::class, new ErasureRequestedIntegrationEvent($builder['identityId'], Clock::get()->now()));

        // Then
        $result = $this->service(ShopperFinderInterface::class)->ofIdOrNull($shopper->id->toString());
        self::assertNotNull($result);
        self::assertSame(ErasureStatus::REQUESTED, $result->erasureStatus);
    }

    #[Test]
    public function itIgnoresWhenNoneExist(): void
    {
        // When
        $this->trigger(RequestShopperErasureOnErasureRequested::class, new ErasureRequestedIntegrationEvent(Uuid::uuid7()->toString(), Clock::get()->now()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
