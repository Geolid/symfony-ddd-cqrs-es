<?php

declare(strict_types=1);

namespace Tools\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use Ramsey\Uuid\Uuid;

/**
 * @implements Rule<StaticCall>
 */
final class ForbidRamseyUuidInTestsRule implements Rule
{
    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    /**
     * @return list<RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->class instanceof Name) {
            return [];
        }

        if (Uuid::class !== ltrim((string) $node->class, '\\')) {
            return [];
        }

        if (!$this->isTestFile($scope->getFile()) || $this->isExempt($scope->getFile())) {
            return [];
        }

        return [
            RuleErrorBuilder::message(\sprintf(
                'Forbidden: %s used directly in a test. Use the aggregate\'s Test Factory instead, '
                .'e.g. XTestFactory::new()->attribute(\'id\')->toString().',
                Uuid::class,
            ))->identifier('app.tests.noRamseyUuid')->build(),
        ];
    }

    private function isTestFile(string $file): bool
    {
        return str_contains($file, '/tests/');
    }

    private function isExempt(string $file): bool
    {
        if (str_contains($file, '/Domain/')) {
            return true;
        }

        $basename = basename($file);

        return 1 === preg_match('/^Valid[A-Z]\w*Test\.php$/', $basename)
            || 1 === preg_match('/TestFactory\.php$/', $basename);
    }
}
