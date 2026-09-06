<?php

declare(strict_types=1);

namespace Tools\PHPStan\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Tools\PHPStan\Rules\ForbidMultipleDateAnchorsRule;

/**
 * @extends RuleTestCase<ForbidMultipleDateAnchorsRule>
 */
final class ForbidMultipleDateAnchorsRuleTest extends RuleTestCase
{
    public function testRule(): void
    {
        $this->analyse(
            [
                __DIR__.'/data/forbid-multiple-date-anchors/src/Domain/OneInstance.php',
                __DIR__.'/data/forbid-multiple-date-anchors/src/Domain/ModifyOffAnchor.php',
                __DIR__.'/data/forbid-multiple-date-anchors/src/Domain/DifferentMethods.php',
                __DIR__.'/data/forbid-multiple-date-anchors/src/Domain/TwoInstances.php',
                __DIR__.'/data/forbid-multiple-date-anchors/src/NotDomain.php',
                __DIR__.'/data/forbid-multiple-date-anchors/src/RepeatedClockRead.php',
                __DIR__.'/data/forbid-multiple-date-anchors/src/ModifyOffClockAnchor.php',
            ],
            [
                [
                    'Forbidden: a second independently-constructed date instance in this method.',
                    10,
                    'Derive it from the first one via ->modify() instead of a new hand-typed instance or a second Clock::get()->now() call.',
                ],
                [
                    'Forbidden: a second independently-constructed date instance in this method.',
                    12,
                    'Derive it from the first one via ->modify() instead of a new hand-typed instance or a second Clock::get()->now() call.',
                ],
            ],
        );
    }

    protected function getRule(): Rule
    {
        return new ForbidMultipleDateAnchorsRule();
    }
}
