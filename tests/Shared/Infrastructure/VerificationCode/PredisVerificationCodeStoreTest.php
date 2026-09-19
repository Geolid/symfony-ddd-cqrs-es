<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\VerificationCode;

use PHPUnit\Framework\Attributes\Test;
use Predis\Client;
use Shared\Domain\ValueObject\VerificationCodeKey;
use Shared\Infrastructure\VerificationCode\PredisVerificationCodeStore;
use Shared\Tests\Support\Double\DummyVerificationCodePurpose;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class PredisVerificationCodeStoreTest extends AbstractIntegrationTestCase
{
    private PredisVerificationCodeStore $store;
    private VerificationCodeKey $key;
    private VerificationCodeKey $otherKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = $this->service(PredisVerificationCodeStore::class);
        $this->key = VerificationCodeKey::for(DummyVerificationCodePurpose::NAME, 'subject-1');
        $this->otherKey = VerificationCodeKey::for(DummyVerificationCodePurpose::OTHER, 'subject-1');
    }

    #[Test]
    public function itFinds(): void
    {
        // Given
        $expiresAt = Clock::get()->now()->modify('+15 minutes');
        $this->store->save($this->key, 'hash-1', $expiresAt);

        // When
        $record = $this->store->find($this->key);

        // Then
        self::assertNotNull($record);
        self::assertSame('hash-1', $record->codeHash);
        self::assertSame($expiresAt->format(\DateTimeInterface::ATOM), $record->expiresAt->format(\DateTimeInterface::ATOM));
        self::assertSame(0, $record->attempts);
    }

    #[Test]
    public function itSetsExpiry(): void
    {
        // Given
        $expiresAt = Clock::get()->now()->modify('+15 minutes');

        // When
        $this->store->save($this->key, 'hash-1', $expiresAt);

        // Then
        self::assertSame(900, $this->ttl($this->key));
    }

    #[Test]
    public function itFloorsExpiryAtOneSecond(): void
    {
        // Given
        $now = Clock::get()->now();

        // When
        $this->store->save($this->key, 'hash-1', $now);

        // Then
        self::assertSame(1, $this->ttl($this->key));
    }

    #[Test]
    public function itFindsNothing(): void
    {
        // When
        $record = $this->store->find($this->key);

        // Then
        self::assertNull($record);
    }

    #[Test]
    public function itReplacesPriorRecord(): void
    {
        // Given
        $now = Clock::get()->now();
        $this->store->save($this->key, 'hash-1', $now->modify('+15 minutes'));
        $this->store->incrementAttempts($this->key);
        $this->store->save($this->otherKey, 'other-hash', $now->modify('+15 minutes'));

        // When
        $newExpiresAt = $now->modify('+30 minutes');
        $this->store->save($this->key, 'hash-2', $newExpiresAt);

        // Then
        $record = $this->store->find($this->key);
        self::assertNotNull($record);
        self::assertSame('hash-2', $record->codeHash);
        self::assertSame($newExpiresAt->format(\DateTimeInterface::ATOM), $record->expiresAt->format(\DateTimeInterface::ATOM));
        self::assertSame(0, $record->attempts);

        $otherRecord = $this->store->find($this->otherKey);
        self::assertNotNull($otherRecord);
        self::assertSame('other-hash', $otherRecord->codeHash);
    }

    #[Test]
    public function itIncrementsAttempts(): void
    {
        // Given
        $this->store->save($this->key, 'hash-1', Clock::get()->now()->modify('+15 minutes'));

        // When
        $this->store->incrementAttempts($this->key);

        // Then
        $record = $this->store->find($this->key);
        self::assertNotNull($record);
        self::assertSame(1, $record->attempts);
    }

    #[Test]
    public function itIgnoresWhenNeverSaved(): void
    {
        // When
        $this->store->incrementAttempts($this->key);

        // Then
        self::assertNull($this->store->find($this->key));
    }

    #[Test]
    public function itDeletes(): void
    {
        // Given
        $expiresAt = Clock::get()->now()->modify('+15 minutes');
        $this->store->save($this->key, 'hash-1', $expiresAt);
        $this->store->save($this->otherKey, 'other-hash', $expiresAt);

        // When
        $this->store->delete($this->key);

        // Then
        self::assertNull($this->store->find($this->key));

        $otherRecord = $this->store->find($this->otherKey);
        self::assertNotNull($otherRecord);
        self::assertSame('other-hash', $otherRecord->codeHash);
    }

    private function ttl(VerificationCodeKey $key): int
    {
        $client = $this->serviceAs('shared.valkey.client', Client::class);
        $prefix = self::getContainer()->getParameter('valkey.key_prefix');
        \assert(\is_string($prefix));

        return $client->ttl($prefix.$key->toString());
    }
}
