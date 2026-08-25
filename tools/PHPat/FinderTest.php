<?php

declare(strict_types=1);

namespace Tools\PHPat;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Shared\Application\Finder\FinderInterface;
use Shared\Infrastructure\Persistence\Projection\Finder\AbstractDbalFinder;

final class FinderTest
{
    #[TestRule]
    public function extendsDbalBase(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(
                Selector::implements(FinderInterface::class),
                Selector::classname('#^Dbal#', true),
                Selector::withFilepath('#/Infrastructure/#', true),
                Selector::Not(Selector::withFilepath('#/vendor/#', true)),
                Selector::Not(Selector::withFilepath('#/tests/#', true)),
                Selector::Not(Selector::isAbstract()),
                Selector::Not(Selector::isInterface()),
            ))
            ->should()->extend()
            ->classes(Selector::classname(AbstractDbalFinder::class))
            ->because('A read implementation rebuilt by hand each time drifts from every sibling doing the same job.');
    }
}
