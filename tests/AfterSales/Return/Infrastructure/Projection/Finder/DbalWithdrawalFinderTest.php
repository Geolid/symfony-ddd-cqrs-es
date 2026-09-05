<?php

declare(strict_types=1);

namespace AfterSales\Tests\Return\Infrastructure\Projection\Finder;

use AfterSales\Return\Application\Finder\Withdrawal\WithdrawalFinderInterface;
use AfterSales\Tests\Return\Support\Builder\WithdrawalBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class DbalWithdrawalFinderTest extends AbstractIntegrationTestCase
{
    private WithdrawalFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(WithdrawalFinderInterface::class);
    }

    #[Test]
    public function itFiltersByOrder(): void
    {
        // Given
        $other = WithdrawalBuilder::new()->create();
        $withdrawal = WithdrawalBuilder::new()->create();
        $this->store($other, $withdrawal);

        // When
        $results = iterator_to_array($this->finder->byOrder($withdrawal->orderId));

        // Then
        self::assertCount(1, $results);
        self::assertSame($withdrawal->id->toString(), $results[0]->id);
    }

    #[Test]
    public function itFiltersActive(): void
    {
        // Given
        $approved = WithdrawalBuilder::new()->received()->approved()->create();
        $requested = WithdrawalBuilder::new()->create();
        $this->store($approved, $requested);

        // When
        $results = iterator_to_array($this->finder->active());

        // Then
        self::assertCount(1, $results);
        self::assertSame($requested->id->toString(), $results[0]->id);
    }
}
