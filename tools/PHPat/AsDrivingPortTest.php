<?php

declare(strict_types=1);

namespace Tools\PHPat;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Shared\Application\Port\AsDrivingPort;

final class AsDrivingPortTest
{
    #[TestRule]
    public function marksOnlyInterfaces(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::appliesAttribute(AsDrivingPort::class))
            ->should()->beInterface()
            ->because('#[AsDrivingPort] declares a port — a contract, never an implementation.');
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
            ->classes(Selector::classname(AsDrivingPort::class))
            ->because('#[AsDrivingPort] is declared on Application ports only — marking Infrastructure or a DM would widen the exposition surface from the wrong side.');
    }
}
