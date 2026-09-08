<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Infrastructure\Projection\Finder;

use Compliance\Erasing\Application\ErasureRequestStatus;
use Compliance\Erasing\Application\Finder\Erasure\ErasureFinderInterface;
use Compliance\Erasing\Application\Finder\Erasure\Exception\ErasureResultNotFoundException;
use Compliance\Erasing\Domain\Erasure;
use Compliance\Tests\Erasing\Support\Builder\ErasureBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Tests\Support\TestCase\AbstractIterableFinderTestCase;
use Symfony\Component\Clock\Clock;

/**
 * @extends AbstractIterableFinderTestCase<\Compliance\Erasing\Application\Finder\Erasure\ErasureResult>
 */
final class DbalErasureFinderTest extends AbstractIterableFinderTestCase
{
    #[Test]
    public function itGetsById(): void
    {
        // Given
        $other = ErasureBuilder::new()->create();
        $erasure = ErasureBuilder::new()->create();
        $this->store($other, $erasure);

        // When
        $result = $this->finder()->ofId($erasure->id->toString());

        // Then
        self::assertSame($erasure->id->toString(), $result->id);
        self::assertSame(ErasureRequestStatus::REQUESTED, $result->status);
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Then
        $this->expectException(ErasureResultNotFoundException::class);

        // When
        $this->finder()->ofId(Uuid::uuid7()->toString());
    }

    #[Test]
    public function itFiltersByRequestedBefore(): void
    {
        // Given
        $now = Clock::get()->now();
        $fresh = ErasureBuilder::new()->withRequestedAt($now->modify('-1 day'))->create();
        $due = ErasureBuilder::new()->withRequestedAt($now->modify('-31 days'))->create();
        $approved = ErasureBuilder::new()->withRequestedAt($now->modify('-31 days'))->approved()->create();
        $this->store($fresh, $due, $approved);

        // When
        $results = iterator_to_array($this->finder()->requestedBefore($now->modify('-30 days')));

        // Then
        self::assertCount(1, $results);
        self::assertSame($due->id->toString(), $results[0]->id);
    }

    protected function finder(): ErasureFinderInterface
    {
        return $this->service(ErasureFinderInterface::class);
    }

    /**
     * @return list<string>
     */
    protected function seed(int $count): array
    {
        $erasures = ErasureBuilder::new()->many($count)->create();
        $this->store(...$erasures);

        return array_map(static fn (Erasure $erasure): string => $erasure->id->toString(), $erasures);
    }

    protected function idOf(object $result): string
    {
        return $result->id;
    }
}
