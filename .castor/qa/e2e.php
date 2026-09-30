<?php

declare(strict_types=1);

use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;
use Symfony\Component\Console\Input\InputOption;

use function Castor\context;
use function Castor\run;
use function Castor\with;

#[AsTask(name: 'e2e', namespace: 'qa', description: 'Run Behat/Playwright real-browser E2E tests')]
function qa_e2e(
    #[AsOption(description: 'Filter scenarios by name')]
    ?string $filter = null,
    #[AsOption(mode: InputOption::VALUE_NONE, description: 'Record a Playwright trace per scenario for review — var/e2e/traces/*.zip, open with `npx playwright show-trace <file>`')]
    ?bool $trace = null,
): void {
    if (!getenv('CI')) {
        run(['docker', 'compose', '--profile', 'e2e', 'up', '-d', 'playwright']);
    }

    with(
        static fn () => compose_exec([
            'vendor/bin/behat', '--config', 'behat.php',
            ...(null !== $filter ? ['--name', $filter] : []),
        ]),
        environment: [
            ...(!getenv('CI') ? ['PLAYWRIGHT_WS_ENDPOINT' => 'ws://playwright:3000/e2e'] : []),
            ...($trace ? ['E2E_TRACE' => '1'] : []),
        ],
        context: context(),
    );
}
