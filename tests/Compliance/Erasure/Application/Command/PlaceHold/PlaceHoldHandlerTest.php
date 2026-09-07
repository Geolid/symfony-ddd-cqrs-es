<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasure\Application\Command\PlaceHold;

use Compliance\Erasure\Application\Command\PlaceHold\PlaceHold;
use Compliance\Erasure\Application\Finder\Subject\SubjectFinderInterface;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class PlaceHoldHandlerTest extends AbstractIntegrationTestCase
{
    private SubjectFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(SubjectFinderInterface::class);
    }

    #[Test]
    public function itPlacesWhenNew(): void
    {
        // Given
        $subjectId = Uuid::uuid7()->toString();

        // When
        $this->dispatch(new PlaceHold($subjectId, 'sales.order.order', Uuid::uuid7()->toString()));

        // Then
        $result = $this->finder->ofId($subjectId);
        self::assertSame(1, $result->activeHoldCount);
    }

    #[Test]
    public function itPlaces(): void
    {
        // Given
        $subjectId = Uuid::uuid7()->toString();
        $this->dispatch(new PlaceHold($subjectId, 'sales.order.order', Uuid::uuid7()->toString()));

        // When
        $this->dispatch(new PlaceHold($subjectId, 'sales.order.order', Uuid::uuid7()->toString()));

        // Then
        $result = $this->finder->ofId($subjectId);
        self::assertSame(2, $result->activeHoldCount);
    }

    #[Test]
    public function itIgnoresWhenAlreadyPlaced(): void
    {
        // Given
        $subjectId = Uuid::uuid7()->toString();
        $sourceId = Uuid::uuid7()->toString();
        $this->dispatch(new PlaceHold($subjectId, 'sales.order.order', $sourceId));

        // When
        $this->dispatch(new PlaceHold($subjectId, 'sales.order.order', $sourceId));

        // Then
        $result = $this->finder->ofId($subjectId);
        self::assertSame(1, $result->activeHoldCount);
    }
}
