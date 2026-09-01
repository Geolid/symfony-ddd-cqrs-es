<?php

declare(strict_types=1);

namespace Tools\PHPStan\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Tools\PHPStan\Rules\ForbidInlineCallInAssertionRule;

/**
 * @extends RuleTestCase<ForbidInlineCallInAssertionRule>
 */
final class ForbidInlineCallInAssertionRuleTest extends RuleTestCase
{
    public function testRule(): void
    {
        $message = 'Forbidden: a method/static call nested inside an assertion argument hides what is actually being '
            .'checked. Assign it to a variable in // When or // Then first, then assert against that variable.';

        $this->analyse([__DIR__.'/data/forbid-inline-call-in-assertion.php'], [
            [$message, 19],
            [$message, 20],
            [$message, 21],
        ]);
    }

    protected function getRule(): Rule
    {
        return new ForbidInlineCallInAssertionRule($this->createReflectionProvider());
    }
}
