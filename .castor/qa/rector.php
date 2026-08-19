<?php

declare(strict_types=1);

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;
use Symfony\Component\Console\Input\InputOption;

#[AsTask(name: 'rector', namespace: 'qa', description: 'Check (or apply) Rector refactoring rules (optional: a target file or directory)')]
function qaRector(
    #[AsOption(mode: InputOption::VALUE_NONE, description: 'Apply fixes instead of just checking')]
    bool $fix,
    #[AsArgument(description: 'Target file or directory')]
    ?string $target = null,
): void {
    dockerExec(['mkdir', '-p', 'var/rector/tmp']);

    dockerExec([
        'env', 'TMPDIR=/srv/var/rector/tmp', 'vendor/bin/rector', 'process',
        ...(!$fix ? ['--dry-run'] : []),
        ...(null !== $target ? [$target] : []),
    ]);
}
