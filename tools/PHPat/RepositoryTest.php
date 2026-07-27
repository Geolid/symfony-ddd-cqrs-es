<?php

declare(strict_types=1);

namespace Tools\PHPat;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

final class RepositoryTest
{
    #[TestRule]
    public function implementsItsDomainPort(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(
                Selector::classname('#Repository$#', true),
                Selector::withFilepath('#/Infrastructure/#', true),
                Selector::Not(Selector::withFilepath('#/vendor/#', true)),
            ))
            ->should()->implement()
            ->classes(Selector::classname('#RepositoryInterface$#', true))
            ->because('An Infrastructure repository is the adapter of its Domain port.');
    }
}
