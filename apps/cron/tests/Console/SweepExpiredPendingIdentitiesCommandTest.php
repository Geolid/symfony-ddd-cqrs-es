<?php

declare(strict_types=1);

namespace Cron\Tests\Console;

use Cron\Tests\AbstractCronTestCase;
use Iam\Identity\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Console\Command\Command;

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
        $expiry = '24 hours';
        $fresh = IdentityFactory::new()->withExpiry($expiry)->withRegisteredAt($now->modify(\sprintf('-%s +1 second', $expiry)))->create();
        $stale = IdentityFactory::new()->withExpiry($expiry)->withRegisteredAt($now->modify(\sprintf('-%s -1 second', $expiry)))->create();
        $this->store($fresh, $stale);

        // When
        $this->tester()->run(['command' => 'iam:identity:sweep-expired-pending']);

        // Then
        $result = $this->identityFinder->ofId($fresh->id->toString());
        self::assertSame($fresh->id->toString(), $result->id);

        $this->expectException(IdentityResultNotFoundException::class);

        $this->identityFinder->ofId($stale->id->toString());
    }

    #[Test]
    public function itSkips(): void
    {
        // Given
        $now = Clock::get()->now();
        $expiry = '24 hours';
        $stale = IdentityFactory::new()->withExpiry($expiry)->withRegisteredAt($now->modify(\sprintf('-%s -1 second', $expiry)))->create();
        $this->store($stale);
        $lock = $this->lock('iam:identity:sweep-expired-pending');

        try {
            // When
            $status = $this->tester()->run(['command' => 'iam:identity:sweep-expired-pending']);

            // Then
            $result = $this->identityFinder->ofId($stale->id->toString());
            self::assertSame(Command::SUCCESS, $status);
            self::assertSame($stale->id->toString(), $result->id);
        } finally {
            $lock->release();
        }
    }
}
