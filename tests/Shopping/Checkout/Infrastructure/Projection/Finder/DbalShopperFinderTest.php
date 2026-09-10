<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Infrastructure\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\ErasureStatus;
use Shopping\Checkout\Application\Finder\Shopper\Exception\ShopperResultNotFoundException;
use Shopping\Checkout\Application\Finder\Shopper\ShopperFinderInterface;
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
    public function itGetsById(): void
    {
        // Given
        $other = ShopperBuilder::new()->create();
        $builder = ShopperBuilder::new();
        $shopper = $builder->create();
        $this->store($other, $shopper);

        // When
        $result = $this->finder->ofId($shopper->id->toString());

        // Then
        self::assertSame($shopper->id->toString(), $result->id);
        self::assertSame($builder['email']->value, $result->email);
        self::assertSame(
            $builder['registeredAt']->format(\DateTimeInterface::ATOM),
            $result->registeredAt->format(\DateTimeInterface::ATOM),
        );
        self::assertSame(ErasureStatus::RETAINED, $result->erasureStatus);
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Then
        $this->expectException(ShopperResultNotFoundException::class);

        // When
        $this->finder->ofId(Uuid::uuid7()->toString());
    }
}
