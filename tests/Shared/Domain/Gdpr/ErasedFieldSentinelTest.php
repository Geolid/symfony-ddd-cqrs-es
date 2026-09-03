<?php

declare(strict_types=1);

namespace Shared\Tests\Domain\Gdpr;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Domain\Gdpr\ErasedFieldSentinel;

final class ErasedFieldSentinelTest extends TestCase
{
    private const string SUBJECT_ID = '10000000-7000-0000-0000-000000000001';

    #[Test]
    public function itReturnsFallback(): void
    {
        // Given
        $sentinel = new ErasedFieldSentinel('erased');

        // When
        $result = $sentinel(self::SUBJECT_ID);

        // Then
        self::assertSame('erased', $result);
    }

    #[Test]
    public function itReturnsNonStringFallback(): void
    {
        // Given
        $fallback = ['static' => 'erased', 'code' => '00000'];
        $sentinel = new ErasedFieldSentinel($fallback);

        // When
        $result = $sentinel(self::SUBJECT_ID);

        // Then
        self::assertSame($fallback, $result);
    }

    #[Test]
    public function itHashesSubjectId(): void
    {
        // Given
        $sentinel = new ErasedFieldSentinel('%s-erased');

        // When
        $erased = $sentinel(self::SUBJECT_ID);
        $anotherErased = $sentinel('10000000-7000-0000-0000-000000000002');

        // Then
        self::assertSame('f0704062-erased', $erased);
        self::assertNotSame($erased, $anotherErased);
    }

    #[Test]
    public function itResolvesNestedSentinel(): void
    {
        // Given
        $sentinel = new ErasedFieldSentinel([
            'static' => 'erased',
            'hashed' => new ErasedFieldSentinel('%s-erased'),
        ]);

        // When
        $result = $sentinel(self::SUBJECT_ID);

        // Then
        self::assertIsArray($result);
        self::assertSame('erased', $result['static']);
        self::assertIsString($result['hashed']);
        self::assertMatchesRegularExpression('/^[0-9a-f]{8}-erased$/', $result['hashed']);
    }
}
