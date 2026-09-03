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
                'Forbidden: new \DateTime() without arguments implicitly uses the system clock.',
                11,
                'Use $this->clock->now() instead.',
            ],
            [
                'Forbidden: new \DateTimeImmutable() without arguments implicitly uses the system clock.',
                12,
                'Use $this->clock->now() instead.',
            ],
            [
                'Forbidden: new \DateTimeImmutable(\'2024-01-01\') has no explicit timezone offset.',
                13,
                'Use \DateTimeInterface::ATOM format, e.g. \'2024-01-01T00:00:00+00:00\'.',
            ],
            [
                'Forbidden: new \DateTimeImmutable($row[...]) rehydrating a database column with no explicit '
                .'\DateTimeZone argument implicitly uses the server\'s local timezone.',
                14,
                'Pass new \DateTimeZone(\'UTC\') as the second argument.',
            ],
        ]);
    }

    protected function getRule(): Rule
    {
        return new ForbidDateTimeImmutableWithoutTimezoneRule();
    }
}
