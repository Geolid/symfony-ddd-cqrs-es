<?php

declare(strict_types=1);

namespace Tools\PHPStan\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Tools\PHPStan\Rules\ForbidRepeatedServiceResolutionRule;

/**
 * @extends RuleTestCase<ForbidRepeatedServiceResolutionRule>
 */
final class ForbidRepeatedServiceResolutionRuleTest extends RuleTestCase
{
    public function testRule(): void
    {
        $message = 'Forbidden: SomeFinderInterface resolved 2 times in this class.';
        $tip = 'Hoist the repeated resolution to a setUp()-assigned property instead.';

        $this->analyse([__DIR__.'/data/forbid-repeated-service-resolution.php'], [
            [$message, 36, $tip],
            [$message, 41, $tip],
        ]);
    }

    protected function getRule(): Rule
    {
        return new ForbidRepeatedServiceResolutionRule();
    }
}
