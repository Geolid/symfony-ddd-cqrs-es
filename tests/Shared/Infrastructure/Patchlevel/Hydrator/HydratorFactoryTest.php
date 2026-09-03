<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Patchlevel\Hydrator;

use Patchlevel\Hydrator\StackHydrator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Infrastructure\Patchlevel\Hydrator\HydratorFactory;
use Shared\Infrastructure\Patchlevel\Hydrator\Metadata\SnakeCaseFieldNameEnricher;
use Shared\Infrastructure\Patchlevel\Hydrator\Metadata\TypeBasedNormalizerEnricher;
use Shared\Tests\Support\Double\DummyHydratable;
use Symfony\Component\Clock\Clock;

final class HydratorFactoryTest extends TestCase
{
    private const string DATE_FORMAT = 'Y-m-d H:i:s';

    private StackHydrator $hydrator;

    protected function setUp(): void
    {
        $this->hydrator = new HydratorFactory(new SnakeCaseFieldNameEnricher(), new TypeBasedNormalizerEnricher())->create();
    }

    #[Test]
    public function itHydrates(): void
    {
        // Given
        $at = Clock::get()->now();

        // When
        $object = $this->hydrator->hydrate(DummyHydratable::class, [
            'date_time' => $at->format(self::DATE_FORMAT),
        ]);

        // Then
        self::assertNotNull($object->dateTime);
        self::assertSame(
            $at->format(self::DATE_FORMAT),
            $object->dateTime->format(self::DATE_FORMAT),
        );
    }

    #[Test]
    public function itExtracts(): void
    {
        // Given
        $at = Clock::get()->now();
        $object = new DummyHydratable(dateTime: $at);

        // When
        $data = $this->hydrator->extract($object);

        // Then
        self::assertSame($at->format(self::DATE_FORMAT), $data['date_time']);
    }
}
