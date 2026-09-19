<?php

declare(strict_types=1);

namespace Shared\Tests\Domain\ValueObject;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Domain\ValueObject\VerificationCodeKey;
use Shared\Tests\Support\Double\DummyVerificationCodePurpose;
use Webmozart\Assert\InvalidArgumentException;

final class VerificationCodeKeyTest extends TestCase
{
    #[Test]
    public function itCreates(): void
    {
        // When
        $key = VerificationCodeKey::for(DummyVerificationCodePurpose::NAME, 'subject-1');

        // Then
        self::assertSame('dummy.name:subject-1', $key->toString());
    }

    #[Test]
    public function itProtectsInvariants(): void
    {
        // Then
        $this->expectException(InvalidArgumentException::class);

        // When
        VerificationCodeKey::for(DummyVerificationCodePurpose::NAME, '');
    }

    #[Test]
    public function itEquals(): void
    {
        // Given
        $a = VerificationCodeKey::for(DummyVerificationCodePurpose::NAME, 'subject-1');
        $b = VerificationCodeKey::for(DummyVerificationCodePurpose::NAME, 'subject-1');

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = VerificationCodeKey::for(DummyVerificationCodePurpose::NAME, 'subject-1');
        $differentSubject = VerificationCodeKey::for(DummyVerificationCodePurpose::NAME, 'subject-2');
        $differentPurpose = VerificationCodeKey::for(DummyVerificationCodePurpose::OTHER, 'subject-1');

        // When
        $differsOnSubject = $a->equals($differentSubject);
        $differsOnPurpose = $a->equals($differentPurpose);

        // Then
        self::assertFalse($differsOnSubject);
        self::assertFalse($differsOnPurpose);
    }
}
