<?php

declare(strict_types=1);

namespace Shared\Tests\Domain\Gdpr;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Domain\Gdpr\ErasedFieldSentinel;

final class ErasedFieldSentinelTest extends TestCase
{
    #[Test]
    public function itReturnsAGenericFallbackUnchanged(): void
    {
        // Given
        $sentinel = new ErasedFieldSentinel('Erased');

        // When
        $result = $sentinel('10000000-7000-0000-0000-000000000001');

        // Then
        self::assertSame('Erased', $result);
    }

    #[Test]
    public function itReturnsANonStringFallbackUnchanged(): void
    {
        // Given
        $sentinel = new ErasedFieldSentinel(['firstName' => 'Erased', 'postalCode' => '00000']);

        // When
        $result = $sentinel('10000000-7000-0000-0000-000000000001');

        // Then
        self::assertSame(['firstName' => 'Erased', 'postalCode' => '00000'], $result);
    }

    #[Test]
    public function itHashesTheSubjectIdIntoATemplatedFallback(): void
    {
        // Given
        $sentinel = new ErasedFieldSentinel('erased-email-%s@customer.invalid');

        // When
        $first = $sentinel('10000000-7000-0000-0000-000000000001');
        $second = $sentinel('10000000-7000-0000-0000-000000000002');

        // Then
        self::assertIsString($first);
        self::assertMatchesRegularExpression('/^erased-email-[0-9a-f]{8}@customer\.invalid$/', $first);
        self::assertNotSame($first, $second);
    }

    #[Test]
    public function itResolvesANestedSentinelWithinAnArrayFallback(): void
    {
        // Given
        $sentinel = new ErasedFieldSentinel([
            'firstName' => 'Erased',
            'email' => new ErasedFieldSentinel('erased-email-%s@customer.invalid'),
        ]);

        // When
        $result = $sentinel('10000000-7000-0000-0000-000000000001');

        // Then
        self::assertIsArray($result);
        self::assertSame('Erased', $result['firstName']);
        self::assertIsString($result['email']);
        self::assertMatchesRegularExpression('/^erased-email-[0-9a-f]{8}@customer\.invalid$/', $result['email']);
    }
}
