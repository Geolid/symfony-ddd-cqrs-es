<?php

declare(strict_types=1);

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;
use Symfony\Component\Console\Input\InputOption;

use function Castor\io;

#[AsTask(name: 'cs', namespace: 'qa', description: 'Check coding standards')]
function qa_cs(
    #[AsOption(description: 'Restrict to "php" or "twig" (default: both)', autocomplete: ['php', 'twig'])]
    ?string $type = null,
    #[AsArgument(description: 'Target file or directory')]
    ?string $target = null,
    #[AsOption(mode: InputOption::VALUE_NONE, description: 'Apply the changes instead of just checking (default: dry-run)')]
    ?bool $fix = null,
): void {
    assert_one_of($type, ['php', 'twig'], 'type');

    if (null === $type || 'twig' === $type) {
        io()->comment('Twig');

        compose_exec([
            'vendor/bin/twig-cs-fixer',
            'lint',
            ...($fix ? ['--fix'] : []),
            ...($target ? [$target] : []),
        ]);
    }

    if (null === $type || 'php' === $type) {
        io()->comment('PHP');

        compose_exec([
            'vendor/bin/php-cs-fixer',
            'fix',
            ...(!$fix ? ['--dry-run', '--diff'] : []),
            ...($target ? [$target] : []),
        ]);
    }
}
