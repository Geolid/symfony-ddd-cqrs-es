<?php

declare(strict_types=1);

namespace Tools\PHPat;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

final class DrivenPortTest
{
    private const array PORT_SUFFIXES = ['Repository', 'Finder', 'Gateway'];

    /**
     * @return iterable<string, Rule>
     */
    #[TestRule]
    public function implementsItsPort(): iterable
    {
        foreach (self::PORT_SUFFIXES as $suffix) {
            yield $suffix => PHPat::rule()
                ->classes(Selector::AllOf(
                    Selector::classname('#'.$suffix.'$#', true),
                    Selector::withFilepath('#/Infrastructure/#', true),
                    Selector::Not(Selector::withFilepath('#/vendor/#', true)),
                    Selector::Not(Selector::withFilepath('#/tests/#', true)),
                    Selector::Not(Selector::isInterface()),
                    Selector::Not(Selector::isAbstract()),
                ))
                ->should()->implement()
                ->classes(Selector::classname('#'.$suffix.'Interface$#', true))
                ->because('An implementation that never commits to its own contract can never be swapped for another.');
        }
    }
}
