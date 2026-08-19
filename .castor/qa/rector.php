<?php

declare(strict_types=1);

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;
use Symfony\Component\Console\Input\InputOption;

#[AsTask(name: 'rector', namespace: 'qa', description: 'Check Rector refactoring rules')]
function qa_rector(
    #[AsArgument(description: 'Target file or directory')]
    ?string $target = null,
    #[AsOption(mode: InputOption::VALUE_NONE, description: 'Apply the changes instead of just checking (default: dry-run)')]
    ?bool $fix = null,
): void {
    compose_exec(['mkdir', '-p', 'var/rector/tmp']);

    compose_exec([
        'env', 'TMPDIR=var/rector/tmp', 'vendor/bin/rector', 'process',
        ...(!$fix ? ['--dry-run'] : []),
        ...(null !== $target ? [$target] : []),
    ]);
}
