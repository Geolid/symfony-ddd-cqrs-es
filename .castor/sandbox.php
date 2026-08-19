<?php

declare(strict_types=1);

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsTask;

use function Castor\run;

#[AsTask(name: 'advance', namespace: 'sandbox', description: 'Advance a sandbox record to its next status, firing the matching webhook')]
function sandbox_advance(
    #[AsArgument(description: 'Fake provider: "acme" or "globex"', autocomplete: ['acme', 'globex'])]
    string $provider,
    #[AsArgument(description: 'The provider reference to advance')]
    string $reference,
): void {
    assert_one_of($provider, ['acme', 'globex'], 'provider');

    run(['docker', 'compose', 'exec', '-T', 'sandbox', 'php', "/var/www/sandbox/{$provider}/cli/advance.php", $reference]);
}
