<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\VerificationCode;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\Exception\VerificationCodeAttemptsExceededException;
use Shared\Domain\Exception\VerificationCodeNotFoundException;
use Shared\Infrastructure\VerificationCode\NativeCodeChallenger;
use Shared\Tests\Support\Double\DummyVerificationCodePurpose;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class NativeCodeChallengerTest extends AbstractIntegrationTestCase
{
    private NativeCodeChallenger $codeChallenger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->codeChallenger = $this->service(NativeCodeChallenger::class);
    }

    #[Test]
    public function itIssues(): void
    {
        // When
        $code = $this->codeChallenger->issue(DummyVerificationCodePurpose::NAME, 'subject-1', Clock::get()->now());

        // Then
        self::assertMatchesRegularExpression('/^\d{6}$/', $code);
    }

    #[Test]
    public function itInvalidatesPriorCodeWhenReissued(): void
    {
        // Given
        $now = Clock::get()->now();
        $firstCode = $this->codeChallenger->issue(DummyVerificationCodePurpose::NAME, 'subject-1', $now);

        // When
        $secondCode = $this->codeChallenger->issue(DummyVerificationCodePurpose::NAME, 'subject-1', $now);

        // Then
        self::assertFalse($this->codeChallenger->verify(DummyVerificationCodePurpose::NAME, 'subject-1', $firstCode, $now));
        self::assertTrue($this->codeChallenger->verify(DummyVerificationCodePurpose::NAME, 'subject-1', $secondCode, $now));
    }

    #[Test]
    #[DataProvider('provideValidTiming')]
    public function itAccepts(string $subjectId, \DateTimeImmutable $issuedAt, \DateTimeImmutable $verifiedAt): void
    {
        // Given
        $code = $this->codeChallenger->issue(DummyVerificationCodePurpose::NAME, $subjectId, $issuedAt);

        // When
        $result = $this->codeChallenger->verify(DummyVerificationCodePurpose::NAME, $subjectId, $code, $verifiedAt);

        // Then
        self::assertTrue($result);
    }

    /**
     * @return iterable<string, array{string, \DateTimeImmutable, \DateTimeImmutable}>
     */
    public static function provideValidTiming(): iterable
    {
        // Truncated to whole seconds: the store isn't guaranteed to preserve sub-second precision.
        $now = Clock::get()->now();
        $now = $now->setTime((int) $now->format('H'), (int) $now->format('i'), (int) $now->format('s'));

        yield 'immediately' => ['subject-1', $now, $now];
        yield 'at expiry boundary' => ['subject-2', $now, $now->modify('+15 minutes')];
    }

    #[Test]
    public function itRefuses(): void
    {
        // Given
        $now = Clock::get()->now();
        $this->codeChallenger->issue(DummyVerificationCodePurpose::NAME, 'subject-1', $now);

        // When
        $result = $this->codeChallenger->verify(DummyVerificationCodePurpose::NAME, 'subject-1', 'wrong', $now);

        // Then
        self::assertFalse($result);
    }

    #[Test]
    public function itFailsWhenNeverIssued(): void
    {
        // Then
        $this->expectException(VerificationCodeNotFoundException::class);

        // When
        $this->codeChallenger->verify(DummyVerificationCodePurpose::NAME, 'subject-1', '123456', Clock::get()->now());
    }

    #[Test]
    public function itFailsWhenAlreadyUsed(): void
    {
        // Given
        $now = Clock::get()->now();
        $code = $this->codeChallenger->issue(DummyVerificationCodePurpose::NAME, 'subject-1', $now);
        $this->codeChallenger->verify(DummyVerificationCodePurpose::NAME, 'subject-1', $code, $now);

        // Then
        $this->expectException(VerificationCodeNotFoundException::class);

        // When
        $this->codeChallenger->verify(DummyVerificationCodePurpose::NAME, 'subject-1', $code, $now);
    }

    #[Test]
    public function itFailsWhenExpired(): void
    {
        // Given
        $now = Clock::get()->now();
        $code = $this->codeChallenger->issue(DummyVerificationCodePurpose::NAME, 'subject-1', $now);

        // Then
        $this->expectException(VerificationCodeNotFoundException::class);

        // When
        $this->codeChallenger->verify(DummyVerificationCodePurpose::NAME, 'subject-1', $code, $now->modify('+16 minutes'));
    }

    #[Test]
    public function itFailsWhenAttemptsExceeded(): void
    {
        // Given
        $now = Clock::get()->now();
        $this->codeChallenger->issue(DummyVerificationCodePurpose::NAME, 'subject-1', $now);
        for ($i = 0; $i < 5; ++$i) {
            $this->codeChallenger->verify(DummyVerificationCodePurpose::NAME, 'subject-1', 'wrong', $now);
        }

        // Then
        $this->expectException(VerificationCodeAttemptsExceededException::class);

        // When
        $this->codeChallenger->verify(DummyVerificationCodePurpose::NAME, 'subject-1', 'wrong', $now);
    }
}
