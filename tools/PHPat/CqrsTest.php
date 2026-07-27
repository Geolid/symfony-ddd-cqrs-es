<?php

declare(strict_types=1);

namespace Tools\PHPat;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

final class CqrsTest
{
    #[TestRule]
    public function queriesNeverTouchTheWriteModel(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(
                Selector::withFilepath('#/Application/Query/#', true),
                Selector::Not(Selector::withFilepath('#/vendor/#', true)),
                Selector::Not(Selector::withFilepath('#/tests/#', true)),
            ))
            ->shouldNot()
            ->dependOn()
            ->classes(Selector::classname('#RepositoryInterface$#', true))
            ->because('A Query reads through a Finder — loading the aggregate couples the read side to the write model.');
    }
}
