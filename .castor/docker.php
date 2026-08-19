<?php

declare(strict_types=1);

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsTask;
use Symfony\Component\Process\ExecutableFinder;

use function Castor\check;
use function Castor\context;
use function Castor\run;

#[AsTask(name: 'up', namespace: 'docker', description: 'Build images and start containers')]
function dockerUp(): void
{
    checkDocker();

    run(['docker', 'compose', 'pull']);
    run(['docker', 'compose', 'build', '--pull']);
    run(['docker', 'compose', 'up', '-d']);
}

#[AsTask(name: 'stop', namespace: 'docker', description: 'Stop and remove containers')]
function dockerStop(): void
{
    checkDocker();

    run(['docker', 'compose', 'down']);
}

#[AsTask(name: 'destroy', namespace: 'docker', description: 'Remove containers, volumes, and networks')]
function dockerDestroy(): void
{
    checkDocker();

    run(['docker', 'compose', 'down', '-v']);
}

#[AsTask(name: 'logs', namespace: 'docker', description: 'Display logs for a service')]
function dockerLog(
    #[AsArgument(description: 'Service name', autocomplete: ['app', 'db', 'mailer', 'nginx', 'sandbox'])]
    string $service,
): void {
    checkDocker();

    $tty = context()->supportsInteraction;

    run(['docker', 'compose', 'logs', '-f', $service], context: context()->withTty($tty)->withPty($tty));
}

function checkDocker(): void
{
    check(
        'Checking Docker is installed',
        'Docker is required — install it from https://docs.docker.com/get-docker/.',
        static fn (): bool => null !== new ExecutableFinder()->find('docker'),
    );
}
