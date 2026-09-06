<?php

declare(strict_types=1);

namespace Tools\PHPStan\Rules;

use PhpParser\Node;
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

        if ($this->isDomainSourceFile($scope->getFile())
            || 'denormalize' === $scope->getFunctionName()) {
            return [];
        }

        $fix = str_contains($scope->getFile(), '/tests/')
            ? 'Clock::get()->now()'
            : 'an injected ClockInterface ($this->clock->now())';

        return [
            RuleErrorBuilder::message(\sprintf('Forbidden: new \%s(...) outside Domain/.', $class))
                ->tip(\sprintf('Use %s instead.', $fix))
                ->identifier('app.datetime.noOutsideClock')->build(),
        ];
    }

    private function isDomainSourceFile(string $file): bool
    {
        return str_contains($file, '/src/') && str_contains($file, '/Domain/');
    }
}
