<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\VerificationCode;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Domain\Exception\VerificationCodeAttemptsExceededException;
use Shared\Domain\Exception\VerificationCodeNotFoundException;
use Shared\Domain\ValueObject\VerificationCodeKey;
use Shared\Infrastructure\Random\NativeNumericCodeGenerator;
use Shared\Infrastructure\VerificationCode\NativeCodeChallenger;
use Shared\Tests\Support\Double\DummyVerificationCodePurpose;
use Shared\Tests\Support\Double\FakeVerificationCodeStore;
use Symfony\Component\Clock\Clock;

final class NativeCodeChallengerTest extends TestCase
{
    private int $maxAttempts;
    private string $expiry;
    private int $digitCount;
    private NativeCodeChallenger $codeChallenger;
    private VerificationCodeKey $key;

    protected function setUp(): void
    {
        $this->maxAttempts = 5;
        $this->expiry = '15 minutes';
        $this->digitCount = 6;
        $this->codeChallenger = new NativeCodeChallenger(
            new FakeVerificationCodeStore(),
            new NativeNumericCodeGenerator(),
            'secret',
            $this->maxAttempts,
            \DateInterval::createFromDateString($this->expiry),
            $this->digitCount,
        );
        $this->key = VerificationCodeKey::for(DummyVerificationCodePurpose::NAME, 'subject-1');
    }

    #[Test]
    public function itIssues(): void
    {
        // When
        $code = $this->codeChallenger->issue($this->key, Clock::get()->now());

        // Then
        self::assertMatchesRegularExpression(\sprintf('/^\d{%d}$/', $this->digitCount), $code);
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
    public function itAccepts(): void
    {
        // Given
        $now = Clock::get()->now();
        $code = $this->codeChallenger->issue($this->key, $now);

        // When
        $result = $this->codeChallenger->verify($this->key, $code, $now);

        // Then
        self::assertTrue($result);
    }

    #[Test]
    public function itAcceptsWhenExpiryReached(): void
    {
        // Given
        $now = Clock::get()->now();
        $code = $this->codeChallenger->issue($this->key, $now);
        $expiresAt = $now->modify(\sprintf('+%s', $this->expiry));

        // When
        $result = $this->codeChallenger->verify($this->key, $code, $expiresAt);

        // Then
        self::assertTrue($result);
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
        $afterExpiry = $now->modify(\sprintf('+%s +1 second', $this->expiry));

        // Then
        $this->expectException(VerificationCodeNotFoundException::class);

        // When
        $this->codeChallenger->verify($this->key, $code, $afterExpiry);
    }

    #[Test]
    public function itFailsWhenAttemptsExceeded(): void
    {
        // Given
        $now = Clock::get()->now();
        $this->codeChallenger->issue($this->key, $now);
        for ($i = 0; $i < $this->maxAttempts; ++$i) {
            $this->codeChallenger->verify($this->key, 'wrong', $now);
        }

        // Then
        $this->expectException(VerificationCodeAttemptsExceededException::class);

        // When
        $this->codeChallenger->verify($this->key, 'wrong', $now);
    }
}
