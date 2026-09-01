<?php

declare(strict_types=1);

namespace Tools\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<ClassMethod>
 */
final class ForbidMultipleDateTimeInstancesRule implements Rule
{
    private const array FORBIDDEN_CLASSES = ['DateTimeImmutable', 'DateTime'];

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @return list<RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $finder = new NodeFinder();

        /** @var list<New_> $instances */
        $instances = $finder->find($node, fn (Node $n): bool => $n instanceof New_ && $this->isForbiddenClass($n));

        if (\count($instances) < 2) {
            return [];
        }

        usort($instances, static fn (New_ $a, New_ $b): int => $a->getStartLine() <=> $b->getStartLine());

        $errors = [];
        foreach (\array_slice($instances, 1) as $instance) {
            $errors[] = RuleErrorBuilder::message(
                'Forbidden: a second independently-constructed date instance in this method. Derive it from the '
                .'first one via ->modify() instead of a new hand-typed instance.',
            )->identifier('app.tests.noMultipleDateTimeInstances')->line($instance->getStartLine())->build();
        }

        return $errors;
    }

    private function isForbiddenClass(New_ $node): bool
    {
        return $node->class instanceof Name && \in_array(ltrim((string) $node->class, '\\'), self::FORBIDDEN_CLASSES, true);
    }
}
