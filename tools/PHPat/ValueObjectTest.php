<?php

declare(strict_types=1);

namespace Tools\PHPat;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

final class ValueObjectTest
{
    #[TestRule]
    public function isReadonly(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(
                Selector::withFilepath('#/Domain/ValueObject/#', true),
                Selector::Not(Selector::withFilepath('#/vendor/#', true)),
                Selector::Not(Selector::withFilepath('#/tests/#', true)),
                Selector::Not(Selector::isInterface()),
                Selector::Not(Selector::isEnum()),
            ))
            ->should()->beReadonly()
            ->because('A Value Object is immutable — a named constructor validates it once, nothing mutates it after.');
    }
}
