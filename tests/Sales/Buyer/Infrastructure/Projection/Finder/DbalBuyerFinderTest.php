<?php

declare(strict_types=1);

namespace Sales\Tests\Buyer\Infrastructure\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Buyer\Application\Finder\Buyer\BuyerFinderInterface;
use Sales\Buyer\Application\Finder\Buyer\Exception\BuyerResultNotFoundException;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class DbalBuyerFinderTest extends AbstractIntegrationTestCase
{
    private BuyerFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(BuyerFinderInterface::class);
    }

    #[Test]
    public function itGetsById(): void
    {
        // Given
        $other = BuyerBuilder::new()->create();
        $builder = BuyerBuilder::new();
        $buyer = $builder->create();
        $this->store($other, $buyer);

        // When
        $result = $this->finder->ofId($buyer->id->toString());

        // Then
        self::assertSame($buyer->id->toString(), $result->id);
        self::assertSame($builder['email']->value, $result->email);
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Then
        $this->expectException(BuyerResultNotFoundException::class);

        // When
        $this->finder->ofId(Uuid::uuid7()->toString());
    }
}
