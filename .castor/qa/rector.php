<?php

declare(strict_types=1);

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;
use Symfony\Component\Console\Input\InputOption;

#[AsTask(name: 'rector', namespace: 'qa', description: 'Check (or apply) Rector refactoring rules')]
function qaRector(
    #[AsArgument(description: 'Target file or directory')]
    ?string $target = null,
    #[AsOption(mode: InputOption::VALUE_NONE, description: 'Apply the changes instead of just checking (default: dry-run)')]
    ?bool $fix = null,
): void {
    dockerExec(['mkdir', '-p', 'var/rector/tmp']);

    dockerExec([
        'env', 'TMPDIR=var/rector/tmp', 'vendor/bin/rector', 'process',
        ...(!$fix ? ['--dry-run'] : []),
        ...(null !== $target ? [$target] : []),
    ]);
}
