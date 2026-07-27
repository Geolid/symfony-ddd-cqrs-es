<?php

declare(strict_types=1);

namespace Tools\PHPat;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

final class ConcreteClassTest
{
    #[TestRule]
    public function isFinal(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(
                Selector::AnyOf(
                    Selector::withFilepath('#/src/#', true),
                    Selector::withFilepath('#/apps/#', true),
                ),
                Selector::Not(Selector::withFilepath('#/vendor/#', true)),
                Selector::Not(Selector::withFilepath('#/tests/#', true)),
            ))
            ->excluding(
                Selector::isAbstract(),
                Selector::isInterface(),
                Selector::isTrait(),
                Selector::isEnum(),
            )
            ->should()->beFinal()
            ->because('Concrete classes are closed by default — an extension point is abstract on purpose.');
    }
}
