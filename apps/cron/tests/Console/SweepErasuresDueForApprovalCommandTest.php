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
        $retentionSeconds = 30 * 24 * 60 * 60;
        $fresh = ErasureFactory::new()->withRetentionSeconds($retentionSeconds)->withRequestedAt($now->modify(\sprintf('-%d seconds', $retentionSeconds - 1)))->create();
        $due = ErasureFactory::new()->withRetentionSeconds($retentionSeconds)->withRequestedAt($now->modify(\sprintf('-%d seconds', $retentionSeconds + 1)))->create();
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
