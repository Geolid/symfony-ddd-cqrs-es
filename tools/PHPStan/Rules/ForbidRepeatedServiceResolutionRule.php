<?php

declare(strict_types=1);

namespace Tools\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Class_>
 */
final class ForbidRepeatedServiceResolutionRule implements Rule
{
    private const array RESOLUTION_METHODS = ['service', 'serviceAs'];

    public function getNodeType(): string
    {
        return Class_::class;
    }

    /**
     * @return list<RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $finder = new NodeFinder();

        if ([] !== $finder->find($node, static fn (Node $n): bool => ($n instanceof MethodCall || $n instanceof StaticCall)
            && $n->name instanceof Identifier
            && \in_array($n->name->toString(), ['createClient', 'browser'], true))) {
            return [];
        }

        /** @var list<MethodCall> $calls */
        $calls = $finder->find($node, static fn (Node $n): bool => $n instanceof MethodCall
            && $n->name instanceof Identifier
            && \in_array($n->name->toString(), self::RESOLUTION_METHODS, true));

        $byClass = [];
        foreach ($calls as $call) {
            $class = $this->resolvedClassArg($call);

            if (null === $class) {
                continue;
            }

            $byClass[$class][] = $call;
        }

        $errors = [];
        foreach ($byClass as $class => $group) {
            if (\count($group) < 2) {
                continue;
            }

            foreach ($group as $call) {
                $errors[] = RuleErrorBuilder::message(\sprintf(
                    'Forbidden: %s resolved %d times in this class. Hoist the repeated resolution to a setUp()-assigned '
                    .'property instead.',
                    $class,
                    \count($group),
                ))->identifier('app.tests.noRepeatedServiceResolution')->line($call->getStartLine())->build();
            }
        }

        return $errors;
    }

    private function resolvedClassArg(MethodCall $call): ?string
    {
        if ([] === $call->getArgs()) {
            return null;
        }

        $arg = $call->getArgs()[0]->value;

        if (!$arg instanceof ClassConstFetch || !$arg->class instanceof Name || !$arg->name instanceof Identifier) {
            return null;
        }

        if ('class' !== $arg->name->toString()) {
            return null;
        }

        return (string) $arg->class;
    }
}
