<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Spike;

use Iam\Identity\Domain\Identity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;

use function Zenstruck\Foundry\faker;

/**
 * SPIKE — throwaway. Plain TestCase: proves Foundry boots without a kernel (UnitTestConfig).
 */
final class FoundrySpikeTest extends TestCase
{
    #[Test]
    public function itCreatesAnAggregateThroughItsNamedConstructor(): void
    {
        $identity = IdentityFactory::new()->create();

        self::assertInstanceOf(Identity::class, $identity);
        self::assertTrue($identity->aggregateRootId()->equals(IdentityFactory::inputs($identity)['id']));
    }

    #[Test]
    public function itAppliesTransitionsAndKeepsInputs(): void
    {
        $identity = IdentityFactory::new()->confirmed()->create();

        self::assertCount(2, $identity->releaseEvents());
        self::assertArrayHasKey('confirmedAt', IdentityFactory::inputs($identity));
    }

    #[Test]
    public function itDerivesValuesFromSeed(): void
    {
        $email = IdentityFactory::inputs(IdentityFactory::new()->create())['email']->value;

        file_put_contents(\dirname(__DIR__, 4).'/var/spike-seed.txt', $email.' | '.faker()->safeEmail()."\n", \FILE_APPEND);

        self::assertNotSame('', $email);
    }
}
