<?php

declare(strict_types=1);

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsTask;
use Symfony\Component\Process\ExecutableFinder;

use function Castor\check;
use function Castor\context;
use function Castor\run;

#[AsTask(name: 'up', namespace: 'docker', description: 'Build images and start containers')]
function docker_up(): void
{
    check_docker();

    run(['docker', 'compose', 'pull']);
    run(['docker', 'compose', 'build', '--pull']);
    run(['docker', 'compose', 'up', '-d']);
}

#[AsTask(name: 'stop', namespace: 'docker', description: 'Stop and remove containers')]
function docker_stop(): void
{
    check_docker();

    run(['docker', 'compose', 'down']);
}

#[AsTask(name: 'destroy', namespace: 'docker', description: 'Remove containers, volumes, and networks')]
function docker_destroy(): void
{
    check_docker();

    run(['docker', 'compose', 'down', '-v']);
}

#[AsTask(name: 'logs', namespace: 'docker', description: 'Display logs for a service')]
function docker_log(
    #[AsArgument(description: 'Service name', autocomplete: ['app', 'db', 'mailer', 'nginx', 'sandbox'])]
    string $service,
): void {
    check_docker();

    $tty = context()->supportsInteraction;

    run(['docker', 'compose', 'logs', '-f', $service], context: context()->withTty($tty)->withPty($tty));
}

function check_docker(): void
{
    check(
        'Checking Docker is installed',
        'Docker is required — install it from https://docs.docker.com/get-docker/.',
        static fn (): bool => null !== new ExecutableFinder()->find('docker'),
    );
}
