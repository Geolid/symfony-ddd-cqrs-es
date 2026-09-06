<?php

declare(strict_types=1);

namespace Tools\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<CallLike>
 */
final readonly class ForbidInlineCallInAssertionRule implements Rule
{
    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }

    public function getNodeType(): string
    {
        return CallLike::class;
    }

    /**
     * @return list<RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof MethodCall && !$node instanceof StaticCall) {
            return [];
        }

        if (!$this->isAssertionCall($node)) {
            return [];
        }

        $errors = [];

        foreach ($node->getArgs() as $arg) {
            if ($this->hasForbiddenNestedCall($arg->value, $scope)) {
                $errors[] = RuleErrorBuilder::message(
                    'Forbidden: a method/static call nested inside an assertion argument hides what is actually '
                    .'being checked.',
                )->tip('Assign it to a variable in // When or // Then first, then assert against that variable.')
                    ->identifier('app.tests.noInlineCallInAssertion')->build();

                break;
            }
        }

        return $errors;
    }

    private function hasForbiddenNestedCall(Node $node, Scope $scope): bool
    {
        if ($node instanceof MethodCall || $node instanceof StaticCall || $node instanceof NullsafeMethodCall) {
            return !($this->isOwnHelperCall($node, $scope) || $this->isPureStaticCall($node, $scope) || $this->isPureEnumMethodCall($node, $scope) || $this->isWhitelistedMethodCall($node));
        }

        foreach ($node->getSubNodeNames() as $subNodeName) {
            $subNode = $node->{$subNodeName};

            foreach (\is_array($subNode) ? $subNode : [$subNode] as $child) {
                if ($child instanceof Node && $this->hasForbiddenNestedCall($child, $scope)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isOwnHelperCall(Node $node, Scope $scope): bool
    {
        if (!$node instanceof MethodCall) {
            return false;
        }

        if (!$node->var instanceof Variable || 'this' !== $node->var->name) {
            return false;
        }

        if (!$node->name instanceof Identifier) {
            return false;
        }

        $class = $scope->getClassReflection();

        if (null === $class || !$class->hasMethod($node->name->toString())) {
            return false;
        }

        return !$class->getMethod($node->name->toString(), $scope)->isPublic();
    }

    private function isPureStaticCall(Node $node, Scope $scope): bool
    {
        if (!$node instanceof StaticCall || !$node->class instanceof Name || !$node->name instanceof Identifier) {
            return false;
        }

        $className = ltrim((string) $node->class, '\\');
        $methodName = $node->name->toString();

        if (\in_array(strtolower($className), ['self', 'static', 'parent'], true)) {
            $class = $scope->getClassReflection();

            return null !== $class && $class->hasNativeMethod($methodName)
                && $class->getNativeMethod($methodName)->hasSideEffects()->no();
        }

        if (!$this->reflectionProvider->hasClass($className)) {
            return false;
        }

        $class = $this->reflectionProvider->getClass($className);

        if (!$class->hasNativeMethod($methodName)) {
            return false;
        }

        return $class->getNativeMethod($methodName)->hasSideEffects()->no();
    }

    private function isPureEnumMethodCall(Node $node, Scope $scope): bool
    {
        if (!$node instanceof MethodCall || !$node->name instanceof Identifier) {
            return false;
        }

        $methodName = $node->name->toString();

        foreach ($scope->getType($node->var)->getObjectClassReflections() as $classReflection) {
            if (!$classReflection->isEnum() || !$classReflection->hasNativeMethod($methodName)) {
                continue;
            }

            if ($classReflection->getNativeMethod($methodName)->hasSideEffects()->no()) {
                return true;
            }
        }

        return false;
    }

    private function isWhitelistedMethodCall(Node $node): bool
    {
        if ((!$node instanceof MethodCall && !$node instanceof NullsafeMethodCall) || !$node->name instanceof Identifier) {
            return false;
        }

        $safeMethods = ['format', 'toString', 'toArray', 'hash', 'verify', 'fetchRow', 'exists', 'needsRehash', 'equals', 'totalItems', 'lastPage', 'currentPage', 'itemsPerPage', 'getTags', 'event', 'header', 'hasHeader', 'getTimezone', 'getName', 'getStatusCode', 'getDisplay', 'getRequest', 'getLocale', 'getPrevious'];

        return \in_array($node->name->toString(), $safeMethods, true);
    }

    private function isAssertionCall(MethodCall|StaticCall $node): bool
    {
        return $node->name instanceof Identifier && str_starts_with($node->name->toString(), 'assert');
    }
}
