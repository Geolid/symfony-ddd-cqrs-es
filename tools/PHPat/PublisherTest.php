<?php

declare(strict_types=1);

namespace Tools\PHPat;

use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Shared\Application\IntegrationEvent\Publisher;

final class PublisherTest
{
    #[TestRule]
    public function appliesTheDedicatedAttribute(): Rule
    {
        return PHPat::rule()
            ->classes($this->publishers())
            ->should()->applyAttribute()
            ->classes(Selector::classname(Publisher::class))
            ->because('A Publisher must apply #[Publisher] — the raw #[Subscriber] leaves group as a free string.');
    }

    private function publishers(): SelectorInterface
    {
        return Selector::AllOf(
            Selector::classname('#Publisher$#', true),
            Selector::withFilepath('#/Application/IntegrationEvent/#', true),
            Selector::Not(Selector::withFilepath('#/Shared/#', true)),
            Selector::Not(Selector::withFilepath('#/vendor/#', true)),
            Selector::Not(Selector::withFilepath('#/tests/#', true)),
            Selector::Not(Selector::isAbstract()),
            Selector::Not(Selector::isInterface()),
        );
    }
}
