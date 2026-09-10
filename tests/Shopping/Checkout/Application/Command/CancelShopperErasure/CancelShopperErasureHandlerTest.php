<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Command\CancelShopperErasure;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\ErasureStatus;
use Shopping\Checkout\Application\Command\CancelShopperErasure\CancelShopperErasure;
use Shopping\Checkout\Application\Finder\Shopper\ShopperFinderInterface;
use Shopping\Checkout\Domain\Exception\ShopperNotFoundException;
use Shopping\Tests\Checkout\Support\Builder\ShopperBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class CancelShopperErasureHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCancels(): void
    {
        // Given
        $shopper = ShopperBuilder::new()->erasureRequested()->create();
        $this->store($shopper);

        // When
        $this->dispatch(new CancelShopperErasure($shopper->id->toString()));

        // Then
        $result = $this->service(ShopperFinderInterface::class)->ofIdOrNull($shopper->id->toString());
        self::assertNotNull($result);
        self::assertSame(ErasureStatus::RETAINED, $result->erasureStatus);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();

        // Then
        $this->expectException(ShopperNotFoundException::class);

        // When
        $this->dispatch(new CancelShopperErasure($id));
    }
}
