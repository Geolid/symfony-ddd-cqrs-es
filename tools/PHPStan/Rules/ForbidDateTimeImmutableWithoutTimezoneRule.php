<?php

declare(strict_types=1);

namespace Tools\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<New_>
 */
final class ForbidDateTimeImmutableWithoutTimezoneRule implements Rule
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

        if ([] === $node->args) {
            return [
                RuleErrorBuilder::message(\sprintf(
                    'Forbidden: new \%s() without arguments implicitly uses the system clock.',
                    $class,
                ))->tip('Use $this->clock->now() instead.')->identifier('app.datetime.noArgs')->build(),
            ];
        }

        $firstArg = $node->args[0];
        if (!$firstArg instanceof Arg) {
            return [];
        }

        if ($firstArg->value instanceof ArrayDimFetch && 1 === \count($node->args)) {
            return [
                RuleErrorBuilder::message(\sprintf(
                    'Forbidden: new \%s($row[...]) rehydrating a database column with no explicit \DateTimeZone '
                    .'argument implicitly uses the server\'s local timezone.',
                    $class,
                ))->tip('Pass new \DateTimeZone(\'UTC\') as the second argument.')
                    ->identifier('app.datetime.noTimezone')->build(),
            ];
        }

        if (!$firstArg->value instanceof String_) {
            return [];
        }

        $value = $firstArg->value->value;

        if (!$this->hasExplicitTimezoneOffset($value)) {
            return [
                RuleErrorBuilder::message(\sprintf(
                    'Forbidden: new \%s(\'%s\') has no explicit timezone offset.',
                    $class,
                    $value,
                ))->tip(\sprintf('Use \DateTimeInterface::ATOM format, e.g. \'%sT00:00:00+00:00\'.', substr($value, 0, 10)))
                    ->identifier('app.datetime.noTimezone')->build(),
            ];
        }

        return [];
    }

    private function hasExplicitTimezoneOffset(string $value): bool
    {
        return (bool) preg_match('/([+-]\d{2}:\d{2}|Z)$/', $value);
    }
}
