<?php

declare(strict_types=1);

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsTask;

use function Castor\fs;
use function Castor\run;

#[AsTask(name: 'acme-advance', namespace: 'sandbox', description: 'Advance an Acme shipment to its next status, firing the matching webhook')]
function sandbox_acme_advance(
    #[AsArgument(description: 'The Acme tracking reference to advance')]
    string $reference,
): void {
    run(['docker', 'compose', 'exec', '-T', 'sandbox', 'php', '/var/www/sandbox/acme/cli/advance.php', $reference]);
}

#[AsTask(name: 'globex-decline', namespace: 'sandbox', description: 'Arm a Globex session to be declined on its next capture')]
function sandbox_globex_decline(
    #[AsArgument(description: 'The Globex session reference to arm')]
    string $reference,
): void {
    run(['docker', 'compose', 'exec', '-T', 'sandbox', 'php', '/var/www/sandbox/globex/cli/decline.php', $reference]);
}

#[AsTask(name: 'reset', namespace: 'sandbox', description: 'Clear every fake provider record')]
function sandbox_reset(): void
{
    fs()->remove(glob(__DIR__.'/../sandbox/data/*.json') ?: []);
}
