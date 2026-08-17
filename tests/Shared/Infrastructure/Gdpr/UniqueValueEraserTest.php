<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Gdpr;

use Patchlevel\EventSourcing\Message\Message;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Shared\Infrastructure\Gdpr\UniqueValueEraser;
use Shared\Tests\Support\Doubles\StubDataSubjectErased;
use Support\Doubles\DummyMessage;

final class UniqueValueEraserTest extends TestCase
{
    /** @var list<string> */
    private array $releasedForSubject = [];

    private UniqueValueRegistryInterface $uniqueValues;

    protected function setUp(): void
    {
        $this->uniqueValues = $this->createStub(UniqueValueRegistryInterface::class);
        $this->uniqueValues->method('releaseAllForSubject')->willReturnCallback(
            function (string $subjectId): void {
                $this->releasedForSubject[] = $subjectId;
            },
        );
    }

    #[Test]
    public function itReleasesReservationsOfAnErasedSubject(): void
    {
        // Given
        $subjectId = Uuid::uuid7()->toString();
        $event = new StubDataSubjectErased($subjectId);

        // When
        (new UniqueValueEraser($this->uniqueValues))(Message::create($event));

        // Then
        self::assertSame([$subjectId], $this->releasedForSubject);
    }

    #[Test]
    public function itIgnoresAnyOtherEvent(): void
    {
        // Given
        $event = new DummyMessage();

        // When
        (new UniqueValueEraser($this->uniqueValues))(Message::create($event));

        // Then
        self::assertSame([], $this->releasedForSubject);
    }
}
