<?php

declare(strict_types=1);

use Castor\Attribute\AsOption;
use Castor\Attribute\AsPathArgument;
use Castor\Attribute\AsTask;
use Symfony\Component\Console\Input\InputOption;

use function Castor\io;

#[AsTask(name: 'cs', namespace: 'qa', description: 'Check coding standards')]
function qa_cs(
    #[AsOption(mode: InputOption::VALUE_NONE, description: 'Apply the fixes instead of only checking')]
    ?bool $fix = null,
): void {
    io()->comment('Twig');
    qa_cs_twig($fix);

    io()->comment('PHP');
    qa_cs_php($fix);
}

/**
 * @param list<string> $args
 */
#[AsTask(name: 'twig', namespace: 'qa:cs', description: 'Check Twig coding standards')]
function qa_cs_twig(
    #[AsOption(mode: InputOption::VALUE_NONE, description: 'Apply the fixes instead of only checking')]
    ?bool $fix = null,
    #[AsPathArgument(description: 'Files or directories', filter: '*.twig')]
    array $args = [],
): void {
    workspace_exec([
        'vendor/bin/twig-cs-fixer',
        'lint',
        '--config=.twig-cs-fixer.dist.php',
        ...($fix ? ['--fix'] : []),
        ...$args,
    ]);
}

/**
 * @param list<string> $args
 */
#[AsTask(name: 'php', namespace: 'qa:cs', description: 'Check PHP coding standards')]
function qa_cs_php(
    #[AsOption(mode: InputOption::VALUE_NONE, description: 'Apply the fixes instead of only checking')]
    ?bool $fix = null,
    #[AsPathArgument(description: 'Files or directories', filter: '*.php')]
    array $args = [],
): void {
    workspace_exec([
        'vendor/bin/php-cs-fixer',
        'fix',
        '--config=.php-cs-fixer.dist.php',
        ...($fix ? [] : ['--dry-run', '--diff']),
        ...$args,
    ]);
}
