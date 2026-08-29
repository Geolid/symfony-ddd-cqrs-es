<?php

declare(strict_types=1);

namespace Tools\PHPat;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Shared\Application\DrivingPort;

final class DrivingPortTest
{
    #[TestRule]
    public function marksOnlyInterfaces(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::appliesAttribute(DrivingPort::class))
            ->should()->beInterface()
            ->because('Marking an implementation instead of its contract exposes internals as the stable surface.');
    }

    #[TestRule]
    public function livesOnlyInApplication(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(
                Selector::Not(Selector::withFilepath('#/Application/#', true)),
                Selector::Not(Selector::withFilepath('#/vendor/#', true)),
                Selector::Not(Selector::withFilepath('#/tools/PHPat/#', true)),
                Selector::Not(Selector::withFilepath('#/tests/#', true)),
                Selector::Not(Selector::withFilepath('#/bootstrap/#', true)),
            ))
            ->shouldNot()
            ->dependOn()
            ->classes(Selector::classname(DrivingPort::class))
            ->because('Marking it from the wrong side widens what\'s exposed instead of narrowing it.');
    }
}
