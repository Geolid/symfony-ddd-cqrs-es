<?php

declare(strict_types=1);

namespace Tools\PHPat;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Shared\Application\Command\CommandBusInterface;

final class ApiTest
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
    public function stateNeverTouchesTheWriteModelDirectly(): Rule
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
            ->because('A Provider/Processor goes through Command/Query bus, never straight at the write-model Repository — a Processor may still ask a Query (e.g. rereading the just-written state to build its response).');
    }
}
