<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Gdpr;

use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Infrastructure\Gdpr\DataSubjectEraserProcessor;
use Shared\Tests\Support\Doubles\StubDataSubjectErased;
use Support\Doubles\DummyMessage;

final class DataSubjectEraserProcessorTest extends TestCase
{
    /** @var list<string> */
    private array $dropped = [];

    private DataSubjectEraserProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $cipherKeyStore = $this->createStub(CipherKeyStore::class);
        $cipherKeyStore->method('removeWithSubjectId')->willReturnCallback(
            function (string $subjectId): void {
                $this->dropped[] = $subjectId;
            },
        );
        $this->processor = new DataSubjectEraserProcessor($cipherKeyStore);
    }

    #[Test]
    public function itDrops(): void
    {
        // Given
        $event = new StubDataSubjectErased('subject-id');

        // When
        ($this->processor)(Message::create($event));

        // Then
        self::assertSame(['subject-id'], $this->dropped);
    }

    #[Test]
    public function itIgnoresWhenNotErasure(): void
    {
        // Given
        $event = new DummyMessage();

        // When
        ($this->processor)(Message::create($event));

        // Then
        self::assertSame([], $this->dropped);
    }
}
