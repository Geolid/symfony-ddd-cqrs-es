<?php

declare(strict_types=1);

namespace Tools\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
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
final class ForbidMultipleDateAnchorsRule implements Rule
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

        $isDomain = str_contains($scope->getFile(), '/src/') && str_contains($scope->getFile(), '/Domain/');

        /** @var list<Node> $instances */
        $instances = [
            ...($isDomain ? $finder->find($node, fn (Node $n): bool => $n instanceof New_ && $this->isForbiddenClass($n)) : []),
            ...$finder->find($node, fn (Node $n): bool => $n instanceof MethodCall && $this->isClockNowCall($n)),
        ];

        if (\count($instances) < 2) {
            return [];
        }

        usort($instances, static fn (Node $a, Node $b): int => $a->getStartLine() <=> $b->getStartLine());

        $errors = [];
        foreach (\array_slice($instances, 1) as $instance) {
            $errors[] = RuleErrorBuilder::message(
                'Forbidden: a second independently-constructed date instance in this method.',
            )->tip('Derive it from the first one via ->modify() instead of a new hand-typed instance or a second Clock::get()->now() call.')
                ->identifier('app.datetime.noMultipleAnchors')->line($instance->getStartLine())->build();
        }

        return $errors;
    }

    private function isForbiddenClass(New_ $node): bool
    {
        return $node->class instanceof Name && \in_array(ltrim((string) $node->class, '\\'), self::FORBIDDEN_CLASSES, true);
    }

    private function isClockNowCall(MethodCall $node): bool
    {
        if (!$node->name instanceof Identifier || 'now' !== $node->name->toString()) {
            return false;
        }

        return $node->var instanceof StaticCall
            && $node->var->class instanceof Name
            && \in_array(ltrim((string) $node->var->class, '\\'), ['Clock', \Symfony\Component\Clock\Clock::class], true)
            && $node->var->name instanceof Identifier
            && 'get' === $node->var->name->toString();
    }
}
