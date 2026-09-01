<?php

declare(strict_types=1);

namespace Tools\PHPStan\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Tools\PHPStan\Rules\ForbidDateTimeImmutableOutsideClockRule;

/**
 * @extends RuleTestCase<ForbidDateTimeImmutableOutsideClockRule>
 */
final class ForbidDateTimeImmutableOutsideClockRuleTest extends RuleTestCase
{
    public function testRule(): void
    {
        $this->analyse(
            [
                __DIR__.'/data/forbid-datetime-outside-clock/src/Domain/InDomain.php',
                __DIR__.'/data/forbid-datetime-outside-clock/src/RawRow.php',
                __DIR__.'/data/forbid-datetime-outside-clock/src/MethodNameExempt.php',
                __DIR__.'/data/forbid-datetime-outside-clock/src/NotClock.php',
            ],
            [
                [
                    'Forbidden: new \DateTimeImmutable(...) outside Domain/ or a raw row rehydration. '
                    .'Use Clock::get()->now() instead.',
                    9,
                ],
            ],
        );
    }

    protected function getRule(): Rule
    {
        return new ForbidDateTimeImmutableOutsideClockRule();
    }
}
