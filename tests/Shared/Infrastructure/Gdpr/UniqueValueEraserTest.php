<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Gdpr;

use Patchlevel\EventSourcing\Message\Message;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Shared\Domain\Gdpr\DataSubjectErasureInterface;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Shared\Infrastructure\Gdpr\UniqueValueEraser;

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
    public function itReleasesEveryReservationOfAnErasedSubject(): void
    {
        // Given
        $subjectId = Uuid::uuid7()->toString();
        $event = new DummyGdprErasure($subjectId);

        // When
        (new UniqueValueEraser($this->uniqueValues))(Message::create($event));

        // Then
        self::assertSame([$subjectId], $this->releasedForSubject);
    }

    #[Test]
    public function itIgnoresAnyOtherEvent(): void
    {
        // Given
        $event = new DummyGdprFact();

        // When
        (new UniqueValueEraser($this->uniqueValues))(Message::create($event));

        // Then
        self::assertSame([], $this->releasedForSubject);
    }
}

final readonly class DummyGdprErasure implements DataSubjectErasureInterface
{
    public function __construct(private string $subjectId)
    {
    }

    public function subjectId(): string
    {
        return $this->subjectId;
    }
}

final readonly class DummyGdprFact
{
}
