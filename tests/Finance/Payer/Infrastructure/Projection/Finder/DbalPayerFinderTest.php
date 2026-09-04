<?php

declare(strict_types=1);

namespace Finance\Tests\Payer\Infrastructure\Projection\Finder;

use Finance\Payer\Application\Exception\PayerResultNotFoundException;
use Finance\Payer\Application\Finder\Payer\PayerFinderInterface;
use Finance\Tests\Payer\Support\Builder\PayerBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class DbalPayerFinderTest extends AbstractIntegrationTestCase
{
    private PayerFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(PayerFinderInterface::class);
    }

    #[Test]
    public function itGetsById(): void
    {
        // Given
        $other = PayerBuilder::new()->create();
        $builder = PayerBuilder::new();
        $payer = $builder->create();
        $this->store($other, $payer);

        // When
        $result = $this->finder->ofId($payer->id->toString());

        // Then
        self::assertSame($payer->id->toString(), $result->id);
        self::assertSame($builder['registeredAt']->format(\DateTimeInterface::ATOM), $result->registeredAt->format(\DateTimeInterface::ATOM));
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Then
        $this->expectException(PayerResultNotFoundException::class);

        // When
        $this->finder->ofId(Uuid::uuid7()->toString());
    }
}
