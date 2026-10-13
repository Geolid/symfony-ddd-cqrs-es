<?php

declare(strict_types=1);

use Castor\Attribute\AsArgsAfterOptionEnd;
use Castor\Attribute\AsArgument;
use Castor\Attribute\AsTask;
use Symfony\Component\Process\Process;

use function Castor\context;
use function Castor\run;

#[AsTask(name: 'up', namespace: 'docker', description: 'Build images and start containers')]
#[NeedsDocker]
function docker_up(): void
{
    run(['docker', 'compose', 'pull']);
    run(['docker', 'compose', 'build', '--pull']);
    run(['docker', 'compose', 'up', '-d']);
}

#[AsTask(name: 'stop', namespace: 'docker', description: 'Stop and remove containers')]
#[NeedsDocker]
function docker_stop(): void
{
    run(['docker', 'compose', 'down']);
}

#[AsTask(name: 'destroy', namespace: 'docker', description: 'Remove containers, volumes, and networks')]
#[NeedsDocker]
function docker_destroy(): void
{
    run(['docker', 'compose', 'down', '-v']);
}

/**
 * @param list<string> $args
 */
#[AsTask(name: 'logs', namespace: 'docker', description: 'Display logs for a service')]
#[NeedsDocker]
function docker_logs(
    #[AsArgument(name: 'service', description: 'Service names', autocomplete: 'docker_services')]
    array $args = [],
): void {
    $tty = context()->supportsInteraction;

    run(['docker', 'compose', 'logs', '-f', ...$args], context: context()->withTty($tty)->withPty($tty));
}

/**
 * @param list<string> $args
 */
#[AsTask(name: 'shell', namespace: 'docker', description: 'Open a shell in the app container', aliases: ['sh'])]
function docker_shell(#[AsArgsAfterOptionEnd] array $args = []): void
{
    workspace_exec([] !== $args ? $args : ['/bin/sh']);
}

/**
 * @return list<string>
 */
function docker_services(): array
{
    $process = new Process(['docker', 'compose', 'config', '--services'], __DIR__.'/..');
    $process->run();

    return array_values(array_filter(explode("\n", $process->getOutput())));
}
