<?php

declare(strict_types=1);

use Castor\Attribute\AsTask;

use function Castor\capture;
use function Castor\context;
use function Castor\fs;
use function Castor\io;
use function Castor\run;

#[AsTask(description: 'Build a production-optimized artifact')]
function dist(): void
{
    $appEnv = appEnv('prod');
    $distPaths = ['bin/console', 'bootstrap', 'config', 'apps', 'public', 'src', '.castor', 'ui', 'vendor', 'castor.php', 'composer.json'];

    io()->title("Building production artifact with APP_ENV={$appEnv}...");

    run(['rm', '-rf', 'vendor/', 'dist/']);
    run(['composer', 'install', '--optimize-autoloader', '--classmap-authoritative', '--prefer-dist', '--no-progress', '--no-dev'], context: context()->withEnvironment(['APP_ENV' => $appEnv]));

    fs()->mkdir('dist');

    $tag = getenv('GITHUB_REF_NAME') ?: capture(['git', 'describe', '--tags', '--always']);
    $branch = capture(['git', 'rev-parse', '--abbrev-ref', 'HEAD']);
    $commit = capture(['git', 'rev-parse', 'HEAD']);

    $date = gmdate('Y-m-d\TH:i:s\Z');

    file_put_contents('dist/release.txt', <<<TXT
        Date: {$date}
        Tag: {$tag}
        Branch: {$branch}
        Commit: {$commit}

        TXT);

    run(['tar', 'czf', 'dist/symfony-ddd-cqrs-es.tar.gz', ...$distPaths, '-C', 'dist', 'release.txt']);

    io()->success('Artifact built at dist/symfony-ddd-cqrs-es.tar.gz');
}
