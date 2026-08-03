<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Domain;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\Order\Domain\PaymentReference;

final class PaymentReferenceTest extends TestCase
{
    #[Test]
    public function itCreates(): void
    {
        // When
        $reference = PaymentReference::fromString('GLBX-9F3K2M1P');

        // Then
        self::assertSame('GLBX-9F3K2M1P', $reference->toString());
    }

    #[Test]
    public function itComparesEquality(): void
    {
        // Given
        $a = PaymentReference::fromString('GLBX-9F3K2M1P');
        $b = PaymentReference::fromString('GLBX-9F3K2M1P');
        $other = PaymentReference::fromString('GLBX-OTHER');

        // Then
        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($other));
    }

    #[Test]
    public function itProtectsInvariants(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        PaymentReference::fromString('');
    }

    #[Test]
    public function itAcceptsAReferenceAtTheMaximumLength(): void
    {
        // Given
        $value = str_repeat('A', 64);

        // When
        $reference = PaymentReference::fromString($value);

        // Then
        self::assertSame($value, $reference->toString());
    }

    #[Test]
    public function itRefusesAReferenceLongerThanTheProviderCanIssue(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        PaymentReference::fromString(str_repeat('A', 65));
    }
}
