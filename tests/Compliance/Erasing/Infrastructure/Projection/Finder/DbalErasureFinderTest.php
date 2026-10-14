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
        $other = ErasureFactory::new()->create();
        $erasure = ErasureFactory::new()->cancelled()->create();
        $this->store($other, $erasure);

        // When
        $result = $this->finder()->ofId($erasure->id->toString());

        // Then
        self::assertSame($erasure->id->toString(), $result->id);
        self::assertSame($erasure->identityId, $result->identityId);
        self::assertSame(ErasureRequestStatus::CANCELLED, $result->status);
        self::assertSameDateTime($erasure->requestedAt, $result->requestedAt);
        self::assertSameDateTime($erasure->effectiveAt, $result->effectiveAt);
        self::assertSameDateTime($erasure->cancelledAt, $result->cancelledAt);
        self::assertNull($result->approvedAt);
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Given
        $unknownId = ErasureIdFactory::new()->create()->toString();

        // Then
        $this->expectException(ErasureResultNotFoundException::class);

        // When
        $this->finder()->ofId($unknownId);
    }

    #[Test]
    public function itGetsRequestedOfIdentity(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $other = ErasureFactory::new()->create();
        $cancelled = ErasureFactory::new()->withIdentityId($identityId)->cancelled()->create();
        $requested = ErasureFactory::new()->withIdentityId($identityId)->create();
        $this->store($other, $cancelled, $requested);

        // When
        $result = $this->finder()->requestedOfIdentityOrNull($identityId);

        // Then
        self::assertNotNull($result);
        self::assertSame($requested->id->toString(), $result->id);
    }

    #[Test]
    public function itGetsNothingRequestedOfIdentityWhenCancelled(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $cancelled = ErasureFactory::new()->withIdentityId($identityId)->cancelled()->create();
        $this->store($cancelled);

        // When
        $result = $this->finder()->requestedOfIdentityOrNull($identityId);

        // Then
        self::assertNull($result);
    }

    #[Test]
    public function itFiltersByEffectiveBefore(): void
    {
        // Given
        $now = Clock::get()->now();
        $fresh = ErasureFactory::new()->withEffectiveAt($now->modify('+1 day'))->create();
        $due = ErasureFactory::new()->withEffectiveAt($now->modify('-1 day'))->create();
        $approved = ErasureFactory::new()->withEffectiveAt($now->modify('-1 day'))->approved($now)->create();
        $this->store($fresh, $due, $approved);

        // When
        $results = iterator_to_array($this->finder()->effectiveBefore($now));

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
