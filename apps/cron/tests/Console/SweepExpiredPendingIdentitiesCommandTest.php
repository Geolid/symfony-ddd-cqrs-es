<?php

declare(strict_types=1);

namespace Cron\Tests\Console;

use Cron\Tests\AbstractCronTestCase;
use Iam\Identity\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Clock\Clock;

final class SweepExpiredPendingIdentitiesCommandTest extends AbstractCronTestCase
{
    private IdentityFinderInterface $identityFinder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->identityFinder = $this->service(IdentityFinderInterface::class);
    }

    #[Test]
    public function itErases(): void
    {
        // Given
        $now = Clock::get()->now();
        $fresh = IdentityBuilder::new()->withRegisteredAt($now->modify('-1 hour'))->create();
        $stale = IdentityBuilder::new()->withRegisteredAt($now->modify('-25 hours'))->create();
        $this->store($fresh, $stale);

        // When
        $this->tester()->run(['command' => 'iam:identity:sweep-expired-pending']);

        // Then
        $result = $this->identityFinder->ofId($fresh->id->toString());
        self::assertSame($fresh->id->toString(), $result->id);

        $this->expectException(IdentityResultNotFoundException::class);

        $this->identityFinder->ofId($stale->id->toString());
    }
}
