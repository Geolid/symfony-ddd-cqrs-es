<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\VerificationCode;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Domain\Exception\VerificationCodeAttemptsExceededException;
use Shared\Domain\Exception\VerificationCodeNotFoundException;
use Shared\Domain\ValueObject\VerificationCodeKey;
use Shared\Infrastructure\VerificationCode\NativeCodeChallenger;
use Shared\Tests\Support\Double\DummyVerificationCodePurpose;
use Shared\Tests\Support\Double\FakeVerificationCodeStore;
use Symfony\Component\Clock\Clock;

final class NativeCodeChallengerTest extends TestCase
{
    private const int MAX_ATTEMPTS = 5;
    private const int EXPIRY_MINUTES = 15;

    private NativeCodeChallenger $codeChallenger;
    private VerificationCodeKey $key;

    protected function setUp(): void
    {
        $this->codeChallenger = new NativeCodeChallenger(new FakeVerificationCodeStore(), 'secret', self::MAX_ATTEMPTS, \sprintf('+%d minutes', self::EXPIRY_MINUTES));
        $this->key = VerificationCodeKey::for(DummyVerificationCodePurpose::NAME, 'subject-1');
    }

    #[Test]
    public function itIssues(): void
    {
        // When
        $code = $this->codeChallenger->issue($this->key, Clock::get()->now());

        // Then
        self::assertMatchesRegularExpression('/^\d{6}$/', $code);
    }

    #[Test]
    public function itInvalidatesPriorCodeWhenReissued(): void
    {
        // Given
        $now = Clock::get()->now();
        $firstCode = $this->codeChallenger->issue($this->key, $now);

        // When
        $secondCode = $this->codeChallenger->issue($this->key, $now);

        // Then
        self::assertFalse($this->codeChallenger->verify($this->key, $firstCode, $now));
        self::assertTrue($this->codeChallenger->verify($this->key, $secondCode, $now));
    }

    #[Test]
    #[DataProvider('provideValidTiming')]
    public function itAccepts(string $subjectId, \DateTimeImmutable $issuedAt, \DateTimeImmutable $verifiedAt): void
    {
        // Given
        $key = VerificationCodeKey::for(DummyVerificationCodePurpose::NAME, $subjectId);
        $code = $this->codeChallenger->issue($key, $issuedAt);

        // When
        $result = $this->codeChallenger->verify($key, $code, $verifiedAt);

        // Then
        self::assertTrue($result);
    }

    /**
     * @return iterable<string, array{string, \DateTimeImmutable, \DateTimeImmutable}>
     */
    public static function provideValidTiming(): iterable
    {
        $now = Clock::get()->now();

        yield 'immediately' => ['subject-1', $now, $now];
        yield 'at expiry boundary' => ['subject-2', $now, $now->modify(\sprintf('+%d minutes', self::EXPIRY_MINUTES))];
    }

    #[Test]
    public function itRefuses(): void
    {
        // Given
        $now = Clock::get()->now();
        $this->codeChallenger->issue($this->key, $now);

        // When
        $result = $this->codeChallenger->verify($this->key, 'wrong', $now);

        // Then
        self::assertFalse($result);
    }

    #[Test]
    public function itFailsWhenNeverIssued(): void
    {
        // Then
        $this->expectException(VerificationCodeNotFoundException::class);

        // When
        $this->codeChallenger->verify($this->key, '123456', Clock::get()->now());
    }

    #[Test]
    public function itFailsWhenAlreadyUsed(): void
    {
        // Given
        $now = Clock::get()->now();
        $code = $this->codeChallenger->issue($this->key, $now);
        $this->codeChallenger->verify($this->key, $code, $now);

        // Then
        $this->expectException(VerificationCodeNotFoundException::class);

        // When
        $this->codeChallenger->verify($this->key, $code, $now);
    }

    #[Test]
    public function itFailsWhenExpired(): void
    {
        // Given
        $now = Clock::get()->now();
        $code = $this->codeChallenger->issue($this->key, $now);

        // Then
        $this->expectException(VerificationCodeNotFoundException::class);

        // When
        $this->codeChallenger->verify($this->key, $code, $now->modify(\sprintf('+%d minutes', self::EXPIRY_MINUTES + 1)));
    }

    #[Test]
    public function itFailsWhenAttemptsExceeded(): void
    {
        // Given
        $now = Clock::get()->now();
        $this->codeChallenger->issue($this->key, $now);
        for ($i = 0; $i < self::MAX_ATTEMPTS; ++$i) {
            $this->codeChallenger->verify($this->key, 'wrong', $now);
        }

        // Then
        $this->expectException(VerificationCodeAttemptsExceededException::class);

        // When
        $this->codeChallenger->verify($this->key, 'wrong', $now);
    }
}
