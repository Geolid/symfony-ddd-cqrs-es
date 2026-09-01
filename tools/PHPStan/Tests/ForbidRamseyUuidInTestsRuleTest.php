<?php

declare(strict_types=1);

namespace Tools\PHPStan\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Ramsey\Uuid\Uuid;
use Tools\PHPStan\Rules\ForbidRamseyUuidInTestsRule;

/**
 * @extends RuleTestCase<ForbidRamseyUuidInTestsRule>
 */
final class ForbidRamseyUuidInTestsRuleTest extends RuleTestCase
{
    public function testRule(): void
    {
        $message = \sprintf(
            'Forbidden: %s used directly in a test. Use the aggregate\'s Builder instead, '
            .'e.g. XBuilder::new()->get(\'id\')->toString().',
            Uuid::class,
        );

        $this->analyse(
            [
                __DIR__.'/data/forbid-ramsey-uuid-in-tests/tests/NotExemptTest.php',
                __DIR__.'/data/forbid-ramsey-uuid-in-tests/tests/Domain/ExemptTest.php',
                __DIR__.'/data/forbid-ramsey-uuid-in-tests/tests/ValidExemptTest.php',
                __DIR__.'/data/forbid-ramsey-uuid-in-tests/tests/ExemptBuilder.php',
                __DIR__.'/data/forbid-ramsey-uuid-in-tests/src/NotATestFile.php',
                __DIR__.'/data/forbid-ramsey-uuid-in-tests/apps/foo/tests/NotExemptAppTest.php',
            ],
            [
                [$message, 11],
                [$message, 11],
            ],
        );
    }

    protected function getRule(): Rule
    {
        return new ForbidRamseyUuidInTestsRule();
    }
}
