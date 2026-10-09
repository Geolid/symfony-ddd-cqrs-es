<?php

declare(strict_types=1);

use Castor\Attribute\AsOption;
use Castor\Attribute\AsPathArgument;
use Castor\Attribute\AsTask;
use Symfony\Component\Console\Input\InputOption;

/**
 * @param list<string> $args
 */
#[AsTask(name: 'rector', namespace: 'qa', description: 'Check Rector refactoring rules')]
function qa_rector(
    #[AsOption(mode: InputOption::VALUE_NONE, description: 'Apply the fixes instead of only checking')]
    ?bool $fix = null,
    #[AsPathArgument(description: 'Files or directories', filter: '*.php')]
    array $args = [],
): void {
    workspace_exec(['mkdir', '-p', 'var/rector/tmp']);
    workspace_exec([
        'env', 'TMPDIR=var/rector/tmp', 'vendor/bin/rector', 'process',
        ...($fix ? [] : ['--dry-run']),
        ...$args,
    ]);
}
