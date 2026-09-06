<?php

declare(strict_types=1);

namespace Tools\PHPStan\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Tools\PHPStan\Rules\ForbidMultipleDateTimeInstancesRule;

/**
 * @extends RuleTestCase<ForbidMultipleDateTimeInstancesRule>
 */
final class ForbidMultipleDateTimeInstancesRuleTest extends RuleTestCase
{
    public function testRule(): void
    {
        $this->analyse([__DIR__.'/data/forbid-multiple-datetime-instances.php'], [
            [
                'Forbidden: a second independently-constructed date instance in this method.',
                40,
                'Derive it from the first one via ->modify() instead of a new hand-typed instance.',
            ],
        ]);
    }

    protected function getRule(): Rule
    {
        return new ForbidMultipleDateTimeInstancesRule();
    }
}
