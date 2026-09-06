<?php

declare(strict_types=1);

namespace Shared\Tests\Domain\Gdpr;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Domain\Gdpr\ErasedFieldSentinel;
use Shared\Domain\Gdpr\ErasedValueObjectSentinel;
use Shared\Tests\Support\Double\DummyValueObject;

final class ErasedValueObjectSentinelTest extends TestCase
{
    private const string SUBJECT_ID = '10000000-7000-0000-0000-000000000001';

    #[Test]
    public function itBuildsFromFallbackArgs(): void
    {
        // Given
        $sentinel = new ErasedValueObjectSentinel(new ErasedFieldSentinel(['erased']), DummyValueObject::class, 'of');

        // When
        $value = $sentinel(self::SUBJECT_ID);

        // Then
        self::assertInstanceOf(DummyValueObject::class, $value);
        self::assertSame('erased', $value->value);
    }

    #[Test]
    public function itBuildsNestedFromComposedSentinel(): void
    {
        // Given
        $sentinel = new ErasedValueObjectSentinel(
            new ErasedFieldSentinel([
                'erased',
                new ErasedValueObjectSentinel(new ErasedFieldSentinel(['erased']), DummyValueObject::class, 'of'),
            ]),
            DummyValueObject::class,
            'of',
        );

        // When
        $value = $sentinel(self::SUBJECT_ID);

        // Then
        self::assertInstanceOf(DummyValueObject::class, $value);
        self::assertSame('erased', $value->value);
        self::assertInstanceOf(DummyValueObject::class, $value->nested);
        self::assertSame('erased', $value->nested->value);
    }
}
