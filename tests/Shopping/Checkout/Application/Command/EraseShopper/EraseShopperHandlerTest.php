<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Command\EraseShopper;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Shopping\Checkout\Application\Command\EraseShopper\EraseShopper;
use Shopping\Checkout\Application\Finder\Shopper\ShopperFinderInterface;
use Shopping\Checkout\Application\ShopperUniqueKey;
use Shopping\Checkout\Domain\Exception\ShopperNotFoundException;
use Shopping\Tests\Checkout\Support\Builder\ShopperBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class EraseShopperHandlerTest extends AbstractIntegrationTestCase
{
    private UniquenessRegistryInterface $uniqueValues;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uniqueValues = $this->service(UniquenessRegistryInterface::class);
    }

    #[Test]
    public function itErases(): void
    {
        // Given
        $shopper = ShopperBuilder::new()->erasureRequested()->create();
        $this->store($shopper);
        $this->uniqueValues->claim(UniqueKey::for(ShopperUniqueKey::EMAIL), $shopper->email->value, $shopper->id->toString());

        // When
        $this->dispatch(new EraseShopper($shopper->id->toString()));

        // Then
        self::assertFalse($this->uniqueValues->isClaimed(UniqueKey::for(ShopperUniqueKey::EMAIL), $shopper->email->value));
        self::assertNull($this->service(ShopperFinderInterface::class)->ofIdOrNull($shopper->id->toString()));
    }

    #[Test]
    public function itIgnoresWhenAlreadyErased(): void
    {
        // Given
        $shopper = ShopperBuilder::new()->erasureRequested()->erased()->create();
        $this->store($shopper);

        // When
        $this->dispatch(new EraseShopper($shopper->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();

        // Then
        $this->expectException(ShopperNotFoundException::class);

        // When
        $this->dispatch(new EraseShopper($id));
    }
}
