<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Gdpr;

use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Shared\Infrastructure\Gdpr\DataSubjectEraserProcessor;
use Shared\Tests\Support\Doubles\StubDataSubjectErased;
use Support\Doubles\DummyMessage;

final class DataSubjectEraserProcessorTest extends TestCase
{
    /** @var list<string> */
    private array $dropped = [];

    private CipherKeyStore $cipherKeyStore;

    protected function setUp(): void
    {
        $this->cipherKeyStore = $this->createStub(CipherKeyStore::class);
        $this->cipherKeyStore->method('removeWithSubjectId')->willReturnCallback(
            function (string $subjectId): void {
                $this->dropped[] = $subjectId;
            },
        );
    }

    #[Test]
    public function itDropsTheKeyOfAnErasedSubject(): void
    {
        // Given
        $subjectId = Uuid::uuid7()->toString();
        $event = new StubDataSubjectErased($subjectId);

        // When
        (new DataSubjectEraserProcessor($this->cipherKeyStore))(Message::create($event));

        // Then
        self::assertSame([$subjectId], $this->dropped);
    }

    #[Test]
    public function itIgnoresAnyOtherEvent(): void
    {
        // Given
        $event = new DummyMessage();

        // When
        (new DataSubjectEraserProcessor($this->cipherKeyStore))(Message::create($event));

        // Then
        self::assertSame([], $this->dropped);
    }
}
