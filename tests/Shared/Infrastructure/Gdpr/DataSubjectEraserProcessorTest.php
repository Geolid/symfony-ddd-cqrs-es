<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Gdpr;

use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\Hydrator\Extension\Cryptography\Cipher\CipherKey;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyNotExists;
use Patchlevel\Hydrator\Extension\Cryptography\Store\InMemoryCipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Infrastructure\Gdpr\DataSubjectEraserProcessor;
use Shared\Tests\Support\Double\StubDataSubjectErased;
use Support\Double\DummyMessage;
use Symfony\Component\Clock\Clock;

final class DataSubjectEraserProcessorTest extends TestCase
{
    private InMemoryCipherKeyStore $cipherKeyStore;
    private CipherKey $key;
    private DataSubjectEraserProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cipherKeyStore = new InMemoryCipherKeyStore();
        $this->key = new CipherKey('key-id', 'subject-id', 'secret', 'method', Clock::get()->now());
        $this->cipherKeyStore->store($this->key);
        $this->processor = new DataSubjectEraserProcessor($this->cipherKeyStore);
    }

    #[Test]
    public function itDrops(): void
    {
        // Given
        $event = new StubDataSubjectErased('subject-id');

        // When
        ($this->processor)(Message::create($event));

        // Then
        $this->expectException(CipherKeyNotExists::class);

        $this->cipherKeyStore->currentKeyFor('subject-id');
    }

    #[Test]
    public function itIgnoresWhenNotErasure(): void
    {
        // Given
        $event = new DummyMessage();

        // When
        ($this->processor)(Message::create($event));

        // Then
        $currentKey = $this->cipherKeyStore->currentKeyFor('subject-id');

        self::assertSame($this->key, $currentKey);
    }
}
