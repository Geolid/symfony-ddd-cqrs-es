<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\VerificationCode;

use PHPUnit\Framework\Attributes\DataProvider;
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
    private Client $client;
    private VerificationCodeKey $key;
    private VerificationCodeKey $otherKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = $this->service(PredisVerificationCodeStore::class);
        $this->client = $this->serviceAs('shared.valkey.client', Client::class);
        $this->key = VerificationCodeKey::for(DummyVerificationCodePurpose::NAME, 'subject-1');
        $this->otherKey = VerificationCodeKey::for(DummyVerificationCodePurpose::OTHER, 'subject-1');
    }

    #[Test]
    #[DataProvider('provideExpiry')]
    public function itSaves(string $modifier, int $expectedTtl): void
    {
        // Given
        $expiresAt = Clock::get()->now()->modify($modifier);

        // When
        $this->store->save($this->key, 'hash-1', $expiresAt);

        // Then
        $raw = $this->fetchRaw($this->key);
        self::assertSame('hash-1', $raw['code_hash']);
        self::assertSame(
            $expiresAt->format(\DateTimeInterface::ATOM),
            $this->denormalize($raw['expires_at'])->format(\DateTimeInterface::ATOM),
        );
        self::assertSame('0', $raw['attempts']);

        self::assertSame($expectedTtl, $this->ttl($this->key));
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function provideExpiry(): iterable
    {
        yield 'normal' => ['+15 minutes', 900];
        yield 'at boundary, floored to one second' => ['+0 seconds', 1];
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
    public function itIgnoresIncrementWhenUnknown(): void
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

    /**
     * @return array<string, string>
     */
    private function fetchRaw(VerificationCodeKey $key): array
    {
        return $this->client->hgetall($key->toString());
    }

    private function ttl(VerificationCodeKey $key): int
    {
        return $this->client->ttl($key->toString());
    }

    private function denormalize(string $value): \DateTimeImmutable
    {
        return new \DateTimeImmutable($value, new \DateTimeZone('UTC'));
    }
}
