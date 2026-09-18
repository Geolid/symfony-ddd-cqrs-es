<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\VerificationCode;

use PHPUnit\Framework\Attributes\Test;
use Shared\Infrastructure\VerificationCode\DbalVerificationCodeStore;
use Shared\Tests\Support\Double\DummyVerificationCodePurpose;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class DbalVerificationCodeStoreTest extends AbstractIntegrationTestCase
{
    private DbalVerificationCodeStore $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = $this->service(DbalVerificationCodeStore::class);
    }

    #[Test]
    public function itSaves(): void
    {
        // Given
        $expiresAt = Clock::get()->now()->modify('+15 minutes');

        // When
        $this->store->save(DummyVerificationCodePurpose::NAME, 'subject-1', 'hash-1', $expiresAt);

        // Then
        $record = $this->store->find(DummyVerificationCodePurpose::NAME, 'subject-1');
        self::assertNotNull($record);
        self::assertSame('hash-1', $record->codeHash);
        self::assertSame($expiresAt->format(\DateTimeInterface::ATOM), $record->expiresAt->format(\DateTimeInterface::ATOM));
        self::assertSame(0, $record->attempts);
    }

    #[Test]
    public function itFindsNothingWhenNeverSaved(): void
    {
        // When
        $record = $this->store->find(DummyVerificationCodePurpose::NAME, 'subject-1');

        // Then
        self::assertNull($record);
    }

    #[Test]
    public function itReplacesPriorRecordOnReissue(): void
    {
        // Given
        $now = Clock::get()->now();
        $this->store->save(DummyVerificationCodePurpose::NAME, 'subject-1', 'hash-1', $now->modify('+15 minutes'));
        $this->store->incrementAttempts(DummyVerificationCodePurpose::NAME, 'subject-1');

        // When
        $newExpiresAt = $now->modify('+30 minutes');
        $this->store->save(DummyVerificationCodePurpose::NAME, 'subject-1', 'hash-2', $newExpiresAt);

        // Then
        $record = $this->store->find(DummyVerificationCodePurpose::NAME, 'subject-1');
        self::assertNotNull($record);
        self::assertSame('hash-2', $record->codeHash);
        self::assertSame($newExpiresAt->format(\DateTimeInterface::ATOM), $record->expiresAt->format(\DateTimeInterface::ATOM));
        self::assertSame(0, $record->attempts);
    }

    #[Test]
    public function itIncrementsAttempts(): void
    {
        // Given
        $this->store->save(DummyVerificationCodePurpose::NAME, 'subject-1', 'hash-1', Clock::get()->now()->modify('+15 minutes'));

        // When
        $this->store->incrementAttempts(DummyVerificationCodePurpose::NAME, 'subject-1');

        // Then
        $record = $this->store->find(DummyVerificationCodePurpose::NAME, 'subject-1');
        self::assertNotNull($record);
        self::assertSame(1, $record->attempts);
    }

    #[Test]
    public function itDeletes(): void
    {
        // Given
        $this->store->save(DummyVerificationCodePurpose::NAME, 'subject-1', 'hash-1', Clock::get()->now()->modify('+15 minutes'));

        // When
        $this->store->delete(DummyVerificationCodePurpose::NAME, 'subject-1');

        // Then
        self::assertNull($this->store->find(DummyVerificationCodePurpose::NAME, 'subject-1'));
    }
}
