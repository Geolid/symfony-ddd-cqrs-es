<?php

declare(strict_types=1);

namespace Sales\Tests\Buyer\Application\Command\EraseBuyer;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Buyer\Application\Command\EraseBuyer\EraseBuyer;
use Sales\Buyer\Application\Finder\Buyer\BuyerFinderInterface;
use Sales\Buyer\Application\Finder\Buyer\Exception\BuyerResultNotFoundException;
use Sales\Buyer\Domain\Exception\BuyerNotFoundException;
use Sales\Buyer\Domain\ValueObject\BuyerUniqueKey;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class EraseBuyerHandlerTest extends AbstractIntegrationTestCase
{
    private UniqueValueRegistryInterface $uniqueValues;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uniqueValues = $this->service(UniqueValueRegistryInterface::class);
    }

    #[Test]
    public function itErases(): void
    {
        // Given
        $buyer = BuyerBuilder::new()->create();
        $this->store($buyer);
        $this->uniqueValues->reserve(UniqueKey::for(BuyerUniqueKey::EMAIL), $buyer->email->value, $buyer->id->toString());

        // When
        $this->dispatch(new EraseBuyer($buyer->id->toString()));

        // Then
        self::assertFalse($this->uniqueValues->exists(UniqueKey::for(BuyerUniqueKey::EMAIL), $buyer->email->value));
        $this->expectException(BuyerResultNotFoundException::class);
        $this->service(BuyerFinderInterface::class)->ofId($buyer->id->toString());
    }

    #[Test]
    public function itIgnoresWhenAlreadyErased(): void
    {
        // Given
        $buyer = BuyerBuilder::new()->erased()->create();
        $this->store($buyer);

        // When
        $this->dispatch(new EraseBuyer($buyer->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();

        // Then
        $this->expectException(BuyerNotFoundException::class);

        // When
        $this->dispatch(new EraseBuyer($id));
    }
}
