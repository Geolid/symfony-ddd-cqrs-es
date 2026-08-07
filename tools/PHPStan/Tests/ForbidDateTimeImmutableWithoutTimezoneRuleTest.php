<?php

declare(strict_types=1);

namespace Tools\PHPStan\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Tools\PHPStan\Rules\ForbidDateTimeImmutableWithoutTimezoneRule;

/**
 * @extends RuleTestCase<ForbidDateTimeImmutableWithoutTimezoneRule>
 */
final class ForbidDateTimeImmutableWithoutTimezoneRuleTest extends RuleTestCase
{
    public function testRule(): void
    {
        $this->analyse([__DIR__.'/data/forbid-date-time-immutable-without-timezone.php'], [
            [
                'Forbidden: new \DateTime() without arguments implicitly uses the system clock. Use $this->clock->now() instead.',
                11,
            ],
            [
                'Forbidden: new \DateTimeImmutable() without arguments implicitly uses the system clock. Use $this->clock->now() instead.',
                12,
            ],
            [
                'Forbidden: new \DateTimeImmutable(\'2024-01-01\') has no explicit timezone offset. Use ISO 8601 with offset, e.g. \'2024-01-01T00:00:00+00:00\'.',
                13,
            ],
            [
                'Forbidden: new \DateTimeImmutable($row[...]) rehydrating a database column with no explicit \DateTimeZone argument implicitly uses the server\'s local timezone. Pass new \DateTimeZone(\'UTC\') as the second argument.',
                14,
            ],
        ]);
    }

    protected function getRule(): Rule
    {
        return new ForbidDateTimeImmutableWithoutTimezoneRule();
    }
}
