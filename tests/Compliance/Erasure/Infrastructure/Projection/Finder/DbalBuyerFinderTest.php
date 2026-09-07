<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasure\Infrastructure\Projection\Finder;

use Compliance\Erasure\Application\Finder\Buyer\BuyerFinderInterface;
use Compliance\Erasure\Application\Finder\Buyer\BuyerResult;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
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
    public function itFindsById(): void
    {
        // Given
        $otherIdentityId = Uuid::uuid7()->toString();
        $other = BuyerBuilder::new()->withIdentityId($otherIdentityId)->create();
        $this->store($other);
        $identityId = Uuid::uuid7()->toString();
        $buyer = BuyerBuilder::new()->withIdentityId($identityId)->create();
        $this->store($buyer);

        // When
        $result = $this->finder->ofIdOrNull($buyer->id->toString());
        $otherResult = $this->finder->ofIdOrNull($other->id->toString());

        // Then
        self::assertInstanceOf(BuyerResult::class, $result);
        self::assertSame($buyer->id->toString(), $result->buyerId);
        self::assertSame($identityId, $result->identityId);

        self::assertInstanceOf(BuyerResult::class, $otherResult);
        self::assertSame($other->id->toString(), $otherResult->buyerId);
        self::assertSame($otherIdentityId, $otherResult->identityId);
    }

    #[Test]
    public function itFindsNoneForUnknownBuyer(): void
    {
        // When
        $result = $this->finder->ofIdOrNull(Uuid::uuid7()->toString());

        // Then
        self::assertNull($result);
    }
}
