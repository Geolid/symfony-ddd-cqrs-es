<?php

declare(strict_types=1);

namespace Cron\Tests\Console;

use Compliance\Erasing\Application\ErasureRequestStatus;
use Compliance\Erasing\Application\Finder\Erasure\ErasureFinderInterface;
use Compliance\Tests\Erasing\Support\Factory\ErasureFactory;
use Cron\Tests\AbstractCronTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Clock\Clock;

final class SweepErasuresDueForApprovalCommandTest extends AbstractCronTestCase
{
    private ErasureFinderInterface $erasureFinder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->erasureFinder = $this->service(ErasureFinderInterface::class);
    }

    #[Test]
    public function itApproves(): void
    {
        // Given
        $now = Clock::get()->now();
        $retention = '30 days';
        $fresh = ErasureFactory::new()->withRetention($retention)->withRequestedAt($now->modify(\sprintf('-%s +1 second', $retention)))->create();
        $due = ErasureFactory::new()->withRetention($retention)->withRequestedAt($now->modify(\sprintf('-%s -1 second', $retention)))->create();
        $this->store($fresh, $due);

        // When
        $this->tester()->run(['command' => 'compliance:erasing:sweep-due-for-approval']);

        // Then
        $freshResult = $this->erasureFinder->ofId($fresh->id->toString());
        $dueResult = $this->erasureFinder->ofId($due->id->toString());
        self::assertSame(ErasureRequestStatus::REQUESTED, $freshResult->status);
        self::assertSame(ErasureRequestStatus::APPROVED, $dueResult->status);
    }
}
