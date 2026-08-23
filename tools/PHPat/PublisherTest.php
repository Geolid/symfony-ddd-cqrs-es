<?php

declare(strict_types=1);

namespace Tools\PHPat;

use Patchlevel\EventSourcing\Store\Store;
use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Shared\Infrastructure\Persistence\EventStore\Publisher\Publisher;

final class PublisherTest
{
    #[TestRule]
    public function neverDependsOnTheStoreDirectly(): Rule
    {
        return PHPat::rule()
            ->classes($this->publishers())
            ->shouldNot()->dependOn()
            ->classes(Selector::classname(Store::class))
            ->because('A Publisher writes to the store only through IntegrationEventAppenderInterface — no direct Store access outside it.');
    }

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
            Selector::withFilepath('#/Infrastructure/Persistence/EventStore/Publisher/#', true),
            Selector::Not(Selector::withFilepath('#/Shared/#', true)),
            Selector::Not(Selector::withFilepath('#/vendor/#', true)),
            Selector::Not(Selector::withFilepath('#/tests/#', true)),
            Selector::Not(Selector::isAbstract()),
            Selector::Not(Selector::isInterface()),
        );
    }
}
