<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Command\RequestShopperErasure;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\ErasureStatus;
use Shopping\Checkout\Application\Command\RequestShopperErasure\RequestShopperErasure;
use Shopping\Checkout\Application\Finder\Shopper\ShopperFinderInterface;
use Shopping\Checkout\Domain\Exception\ShopperNotFoundException;
use Shopping\Tests\Checkout\Support\Builder\ShopperBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class RequestShopperErasureHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRequests(): void
    {
        // Given
        $shopper = ShopperBuilder::new()->create();
        $this->store($shopper);

        // When
        $this->dispatch(new RequestShopperErasure($shopper->id->toString()));

        // Then
        $result = $this->service(ShopperFinderInterface::class)->ofId($shopper->id->toString());
        self::assertSame(ErasureStatus::REQUESTED, $result->erasureStatus);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();

        // Then
        $this->expectException(ShopperNotFoundException::class);

        // When
        $this->dispatch(new RequestShopperErasure($id));
    }
}
