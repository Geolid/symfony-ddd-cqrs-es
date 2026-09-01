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
        $message = 'Forbidden: SomeFinderInterface resolved 2 times in this class. Hoist the repeated resolution to '
            .'a setUp()-assigned property instead.';

        $this->analyse([__DIR__.'/data/forbid-repeated-service-resolution.php'], [
            [$message, 36],
            [$message, 41],
        ]);
    }

    protected function getRule(): Rule
    {
        return new ForbidRepeatedServiceResolutionRule();
    }
}
