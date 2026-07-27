<?php

declare(strict_types=1);

namespace Demo;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'demo:seed', description: 'Seed the demo')]
final readonly class SeedCommand
{
    public function __invoke(OutputInterface $output, Application $application): int
    {
        /** @var list<string> $seeds */
        $seeds = require __DIR__.'/seeds.php';

        foreach ($seeds as $commandName) {
            $returnCode = $application->doRun(new ArrayInput(['command' => $commandName]), $output);

            if (0 !== $returnCode) {
                throw new \RuntimeException(\sprintf('Command "%s" failed with exit code %d.', $commandName, $returnCode));
            }
        }

        return Command::SUCCESS;
    }
}
