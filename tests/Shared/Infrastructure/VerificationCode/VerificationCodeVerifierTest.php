<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\VerificationCode;

use PHPUnit\Framework\Attributes\Test;
use Shared\Application\VerificationCode\VerificationCode;
use Shared\Domain\Exception\VerificationCodeAttemptsExceededException;
use Shared\Domain\Exception\VerificationCodeNotFoundException;
use Shared\Infrastructure\VerificationCode\VerificationCodeVerifier;
use Shared\Tests\Support\Double\DummyVerificationCodePurpose;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class VerificationCodeVerifierTest extends AbstractIntegrationTestCase
{
    private VerificationCode $verificationCode;
    private VerificationCodeVerifier $verifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->verificationCode = $this->service(VerificationCode::class);
        $this->verifier = $this->service(VerificationCodeVerifier::class);
    }

    #[Test]
    public function itVerifies(): void
    {
        // Given
        $now = Clock::get()->now();
        $code = $this->verificationCode->issue(DummyVerificationCodePurpose::NAME, 'subject-1', $now);

        // When
        $result = $this->verifier->verify(DummyVerificationCodePurpose::NAME, 'subject-1', $code, $now);

        // Then
        self::assertTrue($result);
    }

    #[Test]
    public function itVerifiesAtExpiryBoundary(): void
    {
        // Given
        $now = Clock::get()->now();
        $code = $this->verificationCode->issue(DummyVerificationCodePurpose::NAME, 'subject-1', $now);

        // When
        $result = $this->verifier->verify(DummyVerificationCodePurpose::NAME, 'subject-1', $code, $now->modify('+15 minutes'));

        // Then
        self::assertTrue($result);
    }

    #[Test]
    public function itReturnsFalseWhenCodeMismatch(): void
    {
        // Given
        $now = Clock::get()->now();
        $this->verificationCode->issue(DummyVerificationCodePurpose::NAME, 'subject-1', $now);

        // When
        $result = $this->verifier->verify(DummyVerificationCodePurpose::NAME, 'subject-1', 'wrong', $now);

        // Then
        self::assertFalse($result);
    }

    #[Test]
    public function itFailsWhenNeverIssued(): void
    {
        // Then
        $this->expectException(VerificationCodeNotFoundException::class);

        // When
        $this->verifier->verify(DummyVerificationCodePurpose::NAME, 'subject-1', '123456', Clock::get()->now());
    }

    #[Test]
    public function itFailsWhenExpired(): void
    {
        // Given
        $now = Clock::get()->now();
        $code = $this->verificationCode->issue(DummyVerificationCodePurpose::NAME, 'subject-1', $now);

        // Then
        $this->expectException(VerificationCodeNotFoundException::class);

        // When
        $this->verifier->verify(DummyVerificationCodePurpose::NAME, 'subject-1', $code, $now->modify('+16 minutes'));
    }

    #[Test]
    public function itFailsWhenAttemptsExceeded(): void
    {
        // Given
        $now = Clock::get()->now();
        $this->verificationCode->issue(DummyVerificationCodePurpose::NAME, 'subject-1', $now);
        for ($i = 0; $i < 5; ++$i) {
            $this->verifier->verify(DummyVerificationCodePurpose::NAME, 'subject-1', 'wrong', $now);
        }

        // Then
        $this->expectException(VerificationCodeAttemptsExceededException::class);

        // When
        $this->verifier->verify(DummyVerificationCodePurpose::NAME, 'subject-1', 'wrong', $now);
    }
}
