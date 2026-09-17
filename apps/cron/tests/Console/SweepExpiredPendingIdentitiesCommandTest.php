<?php

declare(strict_types=1);

namespace Cron\Tests\Console;

use Cron\Tests\AbstractCronTestCase;
use Iam\Identity\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Console\Tester\CommandTester;
use Webmozart\Assert\Assert;

final class SweepExpiredPendingIdentitiesCommandTest extends AbstractCronTestCase
{
    private IdentityFinderInterface $identityFinder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->identityFinder = $this->service(IdentityFinderInterface::class);
    }

    #[Test]
    public function itErasesExpiredPendingIdentities(): void
    {
        // Given
        $now = Clock::get()->now();
        $stale = IdentityBuilder::new()->withRegisteredAt($now->modify('-25 hours'))->create();
        $fresh = IdentityBuilder::new()->withRegisteredAt($now->modify('-1 hour'))->create();
        $this->store($stale, $fresh);

        $commandTester = $this->commandTester();

        // When
        $commandTester->execute([]);

        // Then
        $this->expectException(IdentityResultNotFoundException::class);

        $this->identityFinder->ofId($stale->id->toString());
    }

    #[Test]
    public function itKeepsUnexpiredPendingIdentities(): void
    {
        // Given
        $now = Clock::get()->now();
        $stale = IdentityBuilder::new()->withRegisteredAt($now->modify('-25 hours'))->create();
        $fresh = IdentityBuilder::new()->withRegisteredAt($now->modify('-1 hour'))->create();
        $this->store($stale, $fresh);

        $commandTester = $this->commandTester();

        // When
        $commandTester->execute([]);

        // Then
        $result = $this->identityFinder->ofId($fresh->id->toString());
        self::assertSame($fresh->id->toString(), $result->id);
    }

    private function commandTester(): CommandTester
    {
        Assert::notNull(self::$kernel);

        return new CommandTester(new Application(self::$kernel)->find('iam:identity:sweep-expired-pending'));
    }
}
