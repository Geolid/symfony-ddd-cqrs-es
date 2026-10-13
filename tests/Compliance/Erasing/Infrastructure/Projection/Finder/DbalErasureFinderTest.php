<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Infrastructure\Projection\Finder;

use Compliance\Erasing\Application\ErasureRequestStatus;
use Compliance\Erasing\Application\Finder\Erasure\ErasureFinderInterface;
use Compliance\Erasing\Application\Finder\Erasure\Exception\ErasureResultNotFoundException;
use Compliance\Erasing\Domain\Erasure;
use Compliance\Tests\Erasing\Support\Factory\ErasureFactory;
use Compliance\Tests\Erasing\Support\Factory\ErasureIdFactory;
use PHPUnit\Framework\Attributes\Test;
use Shared\Tests\Support\TestCase\AbstractIterableFinderTestCase;
use Symfony\Component\Clock\Clock;

/**
 * @extends AbstractIterableFinderTestCase<\Compliance\Erasing\Application\Finder\Erasure\ErasureResult>
 */
final class DbalErasureFinderTest extends AbstractIterableFinderTestCase
{
    private const string DATE_FORMAT = 'Y-m-d H:i:s';

    #[Test]
    public function itGetsById(): void
    {
        // Given
        $other = ErasureFactory::new()->create();
        $erasure = ErasureFactory::new()->cancelled()->create();
        $this->store($other, $erasure);

        // When
        $result = $this->finder()->ofId($erasure->id->toString());

        // Then
        self::assertSame($erasure->id->toString(), $result->id);
        self::assertSame(ErasureRequestStatus::CANCELLED, $result->status);
        self::assertSame(
            $erasure->requestedAt->format(self::DATE_FORMAT),
            $result->requestedAt?->format(self::DATE_FORMAT),
        );
        self::assertSame(
            $erasure->cancelledAt?->format(self::DATE_FORMAT),
            $result->cancelledAt?->format(self::DATE_FORMAT),
        );
        self::assertNull($result->approvedAt);
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Then
        $this->expectException(ErasureResultNotFoundException::class);

        // When
        $this->finder()->ofId(ErasureIdFactory::new()->create()->toString());
    }

    #[Test]
    public function itFiltersByRequestedBefore(): void
    {
        // Given
        $now = Clock::get()->now();
        $fresh = ErasureFactory::new()->withRequestedAt($now->modify('-1 day'))->create();
        $due = ErasureFactory::new()->withRequestedAt($now->modify('-31 days'))->create();
        $approved = ErasureFactory::new()->withRequestedAt($now->modify('-31 days'))->approved()->create();
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
        $erasures = ErasureFactory::new()->many($count)->create();
        $this->store(...$erasures);

        return array_map(static fn (Erasure $erasure): string => $erasure->id->toString(), $erasures);
    }

    protected function indexOf(object $result): string
    {
        return $result->id;
    }
}
