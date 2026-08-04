<?php

declare(strict_types=1);

namespace Tools\PHPat;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Shared\Application\Command\CommandBusInterface;

final class StateProviderTest
{
    #[TestRule]
    public function providersNeverWrite(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(
                Selector::withFilepath('#/State/#', true),
                Selector::withFilepath('#Provider#', true),
                Selector::Not(Selector::withFilepath('#/vendor/#', true)),
                Selector::Not(Selector::withFilepath('#/tests/#', true)),
            ))
            ->shouldNot()
            ->dependOn()
            ->classes(Selector::classname(CommandBusInterface::class))
            ->because('A State Provider serves reads — dispatching a Command puts a side effect behind a GET.');
    }

    #[TestRule]
    public function neverTouchesTheWriteModelDirectly(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(
                Selector::withFilepath('#/State/#', true),
                Selector::Not(Selector::withFilepath('#/vendor/#', true)),
                Selector::Not(Selector::withFilepath('#/tests/#', true)),
            ))
            ->shouldNot()
            ->dependOn()
            ->classes(Selector::classname('#RepositoryInterface$#', true))
            ->because('A Provider/Processor reaches the model only through the Command/Query bus, never the write Repository — a Processor may still issue a Query to reread its own state.');
    }
}
