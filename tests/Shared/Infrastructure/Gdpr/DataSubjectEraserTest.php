<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Gdpr;

use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Domain\Gdpr\DataSubjectErasureInterface;
use Shared\Infrastructure\Gdpr\DataSubjectEraser;

final class DataSubjectEraserTest extends TestCase
{
    /**
     * @var list<string>
     */
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
        $event = new DummyErasure('e2b1e8f6-4b3a-4a3e-9c1e-5f6a7b8c9d01');

        // When
        new DataSubjectEraser($this->cipherKeyStore)->onEvent(Message::create($event));

        // Then
        self::assertSame(['e2b1e8f6-4b3a-4a3e-9c1e-5f6a7b8c9d01'], $this->dropped);
    }

    #[Test]
    public function itKeepsEveryKeyOnAnyOtherEvent(): void
    {
        // Given
        $event = new DummyFact();

        // When
        new DataSubjectEraser($this->cipherKeyStore)->onEvent(Message::create($event));

        // Then
        self::assertSame([], $this->dropped);
    }
}

final readonly class DummyErasure implements DataSubjectErasureInterface
{
    public function __construct(private string $subjectId)
    {
    }

    public function subjectId(): string
    {
        return $this->subjectId;
    }
}

final readonly class DummyFact
{
}
