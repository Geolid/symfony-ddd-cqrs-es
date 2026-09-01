<?php

declare(strict_types=1);

namespace Tools\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<New_>
 */
final class ForbidDateTimeImmutableOutsideClockRule implements Rule
{
    private const array FORBIDDEN_CLASSES = ['DateTimeImmutable', 'DateTime'];

    public function getNodeType(): string
    {
        return New_::class;
    }

    /**
     * @return list<RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->class instanceof Name) {
            return [];
        }

        $class = ltrim((string) $node->class, '\\');

        if (!\in_array($class, self::FORBIDDEN_CLASSES, true)) {
            return [];
        }

        if (str_contains($scope->getFile(), '/Domain/')
            || $this->isRehydratingARawRow($node)
            || 'denormalize' === $scope->getFunctionName()) {
            return [];
        }

        return [
            RuleErrorBuilder::message(\sprintf(
                'Forbidden: new \%s(...) outside Domain/ or a raw row rehydration. Use Clock::get()->now() instead.',
                $class,
            ))->identifier('app.tests.noDateTimeOutsideClock')->build(),
        ];
    }

    private function isRehydratingARawRow(New_ $node): bool
    {
        if ([] === $node->getArgs()) {
            return false;
        }

        return $node->getArgs()[0]->value instanceof ArrayDimFetch;
    }
}
