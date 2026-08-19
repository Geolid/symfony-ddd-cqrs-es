<?php

declare(strict_types=1);

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;
use Symfony\Component\Console\Input\InputOption;

#[AsTask(name: 'cs', namespace: 'qa', description: 'Check (or fix) coding standards (optional: --type=php|twig, a target file or directory)')]
function qaCs(
    #[AsOption(mode: InputOption::VALUE_NONE, description: 'Apply fixes instead of just checking')]
    bool $fix,
    #[AsOption(description: 'Restrict to "php" or "twig" (default: both)', autocomplete: ['php', 'twig'])]
    ?string $type = null,
    #[AsArgument(description: 'Target file or directory')]
    ?string $target = null,
): void {
    $allowedTypes = ['php', 'twig'];

    if (null !== $type && !in_array($type, $allowedTypes, true)) {
        throw new InvalidArgumentException(sprintf('Invalid type "%s". Allowed values are: %s.', $type, implode(', ', $allowedTypes)));
    }

    if (null === $type || 'twig' === $type) {
        dockerExec([
            'vendor/bin/twig-cs-fixer',
            'lint',
            ...($fix ? ['--fix'] : []),
            ...($target ? [$target] : []),
        ]);
    }

    if (null === $type || 'php' === $type) {
        dockerExec([
            'vendor/bin/php-cs-fixer',
            'fix',
            ...(!$fix ? ['--dry-run', '--diff'] : []),
            ...($target ? [$target] : []),
        ]);
    }
}
