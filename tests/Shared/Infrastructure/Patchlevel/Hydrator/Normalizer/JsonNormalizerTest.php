<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Patchlevel\Hydrator\Normalizer;

use Patchlevel\Hydrator\Hydrator;
use Patchlevel\Hydrator\Normalizer\ArrayNormalizer;
use Patchlevel\Hydrator\Normalizer\InvalidArgument;
use Patchlevel\Hydrator\Normalizer\NormalizerWithContext;
use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Infrastructure\Patchlevel\Hydrator\Normalizer\JsonNormalizer;
use Shared\Tests\Support\Double\DummyNestedObject;

final class JsonNormalizerTest extends TestCase
{
    private JsonNormalizer $normalizer;

    protected function setUp(): void
    {
        $inner = new ObjectNormalizer(DummyNestedObject::class);
        $inner->setHydrator(new FakeReflectionHydrator());

        $this->normalizer = new JsonNormalizer($inner);
    }

    #[Test]
    public function itNormalizes(): void
    {
        // When
        $normalized = $this->normalizer->normalize(new DummyNestedObject('x'));

        // Then
        self::assertSame('{"value":"x"}', $normalized);
    }

    #[Test]
    public function itNormalizesNull(): void
    {
        // When
        $normalized = $this->normalizer->normalize(null);

        // Then
        self::assertNull($normalized);
    }

    #[Test]
    public function itDenormalizesFromJsonString(): void
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
    public function itDenormalizesAListOfObjectsFromJsonString(): void
    {
        // Given
        $inner = new ArrayNormalizer(new ObjectNormalizer(DummyNestedObject::class));
        $inner->setHydrator(new FakeReflectionHydrator());
        $normalizer = new JsonNormalizer($inner);

        // When
        /** @var list<DummyNestedObject> $objects */
        $objects = $normalizer->denormalize('[{"value":"x"},{"value":"y"}]');

        // Then
        self::assertSame(['x', 'y'], array_map(static fn (DummyNestedObject $object): string => $object->value, $objects));
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
    public function itNormalizesThroughTheContextAwareCallWhenTheInnerNormalizerSupportsIt(): void
    {
        // Given
        $normalizer = new JsonNormalizer(new SpyContextAwareNormalizer());

        // When
        $normalized = $normalizer->normalize('x');

        // Then
        self::assertSame('2', $normalized);
    }

    #[Test]
    public function itDenormalizesThroughTheContextAwareCallWhenTheInnerNormalizerSupportsIt(): void
    {
        // Given
        $normalizer = new JsonNormalizer(new SpyContextAwareNormalizer());

        // When
        $value = $normalizer->denormalize('"x"');

        // Then
        self::assertSame(2, $value);
    }

    #[Test]
    public function itNeverDelegatesToTheInnerNormalizerOnNull(): void
    {
        // Given
        $normalizer = new JsonNormalizer(new SpyContextAwareNormalizer());

        // When
        $value = $normalizer->denormalize(null);

        // Then
        self::assertNull($value);
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

final class SpyContextAwareNormalizer implements NormalizerWithContext
{
    /**
     * @param array<string, mixed> $context
     */
    public function normalize(mixed $value, array $context = []): mixed
    {
        return \func_num_args();
    }

    /**
     * @param array<string, mixed> $context
     */
    public function denormalize(mixed $value, array $context = []): mixed
    {
        return null === $value ? 'spy-was-called-with-null' : \func_num_args();
    }
}
