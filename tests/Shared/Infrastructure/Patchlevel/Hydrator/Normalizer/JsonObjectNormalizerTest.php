<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Patchlevel\Hydrator\Normalizer;

use Patchlevel\Hydrator\Hydrator;
use Patchlevel\Hydrator\Normalizer\InvalidArgument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Infrastructure\Patchlevel\Hydrator\Normalizer\JsonObjectNormalizer;
use Shared\Tests\Support\Double\DummyNestedObject;

final class JsonObjectNormalizerTest extends TestCase
{
    private JsonObjectNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new JsonObjectNormalizer(DummyNestedObject::class);
        $this->normalizer->setHydrator(new FakeReflectionHydrator());
    }

    #[Test]
    #[DataProvider('provideNormalizableValues')]
    public function itNormalizes(?DummyNestedObject $value, ?string $expected): void
    {
        // When
        $normalized = $this->normalizer->normalize($value);

        // Then
        self::assertSame($expected, $normalized);
    }

    /**
     * @return iterable<string, array{?DummyNestedObject, ?string}>
     */
    public static function provideNormalizableValues(): iterable
    {
        yield 'object' => [new DummyNestedObject('x'), '{"value":"x"}'];
        yield 'null' => [null, null];
    }

    #[Test]
    public function itThrowsWhenNormalizingWrongType(): void
    {
        // Then
        $this->expectException(InvalidArgument::class);

        // When
        $this->normalizer->normalize('x');
    }

    #[Test]
    public function itThrowsWhenNormalizingNoHydrator(): void
    {
        // Given
        $normalizer = new JsonObjectNormalizer(DummyNestedObject::class);

        // Then
        $this->expectException(InvalidArgument::class);

        // When
        $normalizer->normalize(new DummyNestedObject('x'));
    }

    #[Test]
    public function itThrowsWhenNormalizingUnencodableValue(): void
    {
        // Then
        $this->expectException(InvalidArgument::class);

        // When
        $this->normalizer->normalize(new DummyNestedObject("\xB1\x31"));
    }

    #[Test]
    public function itDenormalizes(): void
    {
        // When
        $object = $this->normalizer->denormalize('{"value":"x"}');

        // Then
        self::assertInstanceOf(DummyNestedObject::class, $object);
        self::assertSame('x', $object->value);
    }

    #[Test]
    public function itDenormalizesFromAlreadyDecodedArray(): void
    {
        // When
        $object = $this->normalizer->denormalize(['value' => 'x']);

        // Then
        self::assertInstanceOf(DummyNestedObject::class, $object);
        self::assertSame('x', $object->value);
    }

    #[Test]
    public function itDenormalizesNull(): void
    {
        // When
        $value = $this->normalizer->denormalize(null);

        // Then
        self::assertNull($value);
    }

    #[Test]
    public function itThrowsWhenDenormalizingNoHydrator(): void
    {
        // Given
        $normalizer = new JsonObjectNormalizer(DummyNestedObject::class);

        // Then
        $this->expectException(InvalidArgument::class);

        // When
        $normalizer->denormalize('{"value":"x"}');
    }

    #[Test]
    public function itThrowsWhenDenormalizingInvalidJson(): void
    {
        // When
        try {
            $this->normalizer->denormalize('{invalid');
            self::fail('Expected '.InvalidArgument::class.' to be thrown.');
        } catch (InvalidArgument $exception) {
            // Then
            self::assertInstanceOf(\JsonException::class, $exception->getPrevious());
        }
    }

    #[Test]
    public function itThrowsWhenDenormalizingWrongType(): void
    {
        // Then
        $this->expectException(InvalidArgument::class);

        // When
        $this->normalizer->denormalize(123);
    }

    #[Test]
    public function itThrowsWhenDenormalizingNotAMap(): void
    {
        // Then
        $this->expectException(InvalidArgument::class);

        // When
        $this->normalizer->denormalize('[1,2,3]');
    }
}

final class FakeReflectionHydrator implements Hydrator
{
    public function hydrate(string $class, array $data): object
    {
        $reflection = new \ReflectionClass($class);
        $object = $reflection->newInstanceWithoutConstructor();

        foreach ($data as $property => $value) {
            $reflection->getProperty($property)->setValue($object, $value);
        }

        return $object;
    }

    public function extract(object $object): array
    {
        return get_object_vars($object);
    }
}
