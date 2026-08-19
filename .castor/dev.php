<?php

declare(strict_types=1);

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsTask;
use Symfony\Component\Process\ExecutableFinder;

use function Castor\check;
use function Castor\context;
use function Castor\fs;
use function Castor\run;

#[AsTask(description: 'Install and start the full project')]
function start(): void
{
    if (!fs()->exists(__DIR__.'/../compose.override.yaml')) {
        fs()->copy(__DIR__.'/../compose.override.yaml.dist', __DIR__.'/../compose.override.yaml');
    }

    dockerUp();
    vendor(dev: false);
    hooks();
    dbCreate();
    assets();
}

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

#[AsTask(name: 'log', namespace: 'docker', description: 'Display logs for a service')]
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

#[AsTask(description: 'Install git hooks (CaptainHook)')]
function hooks(): void
{
    dockerExec(['vendor/bin/captainhook', 'install', '-f', '-n']);
}

#[AsTask(description: 'Open shell in app container')]
function sh(
    #[AsArgument(description: 'Command to run instead of an interactive shell')]
    ?string $cmd = null,
): void {
    dockerExec(null !== $cmd ? ['/bin/sh', '-c', $cmd] : ['/bin/sh']);
}

#[AsTask(name: 'dump', description: 'Start Symfony VarDumper server')]
function dumpServer(): void
{
    console(['server:dump']);
}

#[AsTask(description: 'Clear all caches')]
function cc(): void
{
    fs()->remove(glob(__DIR__.'/../var/cache/*') ?: []);
    warmup();
}
