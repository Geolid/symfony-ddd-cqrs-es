<?php

declare(strict_types=1);

namespace Tools\PHPat;

use Patchlevel\EventSourcing\Attribute\OnFailed;
use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Shared\Application\IntegrationEvent\Publisher;
use Shared\Application\Policy\Policy;
use Shared\Application\Processor\Processor;
use Shared\Infrastructure\Persistence\Projection\Projector\Projector;
use Tools\PHPat\Helpers\ConcreteImplementation;

final class SubscriptionTest
{
    #[TestRule]
    public function isReadonly(): Rule
    {
        return PHPat::rule()
            ->classes($this->subscribers())
            ->should()->beReadonly()
            ->because('A subscriber\'s instance is reused across dispatches — mutable state there is a concurrency bug.');
    }

    #[TestRule]
    public function neverHandlesOwnFailure(): Rule
    {
        return PHPat::rule()
            ->classes($this->subscribers())
            ->shouldNot()->dependOn()
            ->classes(Selector::classname(OnFailed::class))
            ->because('A silently skipped failure lets state drift from the truth it should reflect.');
    }

    #[TestRule]
    public function policiesApplyOwnAttribute(): Rule
    {
        return PHPat::rule()
            ->classes($this->policies())
            ->should()->applyAttribute()
            ->classes(Selector::classname(Policy::class))
            ->because('A business reaction to an event never wired up silently never fires.');
    }

    #[TestRule]
    public function processorsApplyOwnAttribute(): Rule
    {
        return PHPat::rule()
            ->classes($this->processors())
            ->should()->applyAttribute()
            ->classes(Selector::classname(Processor::class))
            ->because('A cross-cutting reaction spanning every Bounded Context never wired up silently never runs.');
    }

    #[TestRule]
    public function projectorsApplyOwnAttribute(): Rule
    {
        return PHPat::rule()
            ->classes($this->projectors())
            ->should()->applyAttribute()
            ->classes(Selector::classname(Projector::class))
            ->because('A Projection never wired up stays silently empty.');
    }

    #[TestRule]
    public function publishersApplyOwnAttribute(): Rule
    {
        return PHPat::rule()
            ->classes($this->publishers())
            ->should()->applyAttribute()
            ->classes(Selector::classname(Publisher::class))
            ->because('A fact crossing a Bounded Context boundary needs its own guaranteed processing group — a shared one can\'t promise that.');
    }

    private function subscribers(): SelectorInterface
    {
        return Selector::AllOf(
            Selector::AnyOf(
                Selector::appliesAttribute(Policy::class),
                Selector::appliesAttribute(Processor::class),
                Selector::appliesAttribute(Projector::class),
                Selector::appliesAttribute(Publisher::class),
            ),
            ...ConcreteImplementation::selectors(),
        );
    }

    private function policies(): SelectorInterface
    {
        return Selector::AllOf(
            Selector::withFilepath('#/Application/Policy/#', true),
            Selector::Not(Selector::withFilepath('#/Shared/#', true)),
            ...ConcreteImplementation::selectors(),
        );
    }

    private function processors(): SelectorInterface
    {
        return Selector::AllOf(
            Selector::withFilepath('#/Application/Processor/#', true),
            Selector::Not(Selector::withFilepath('#/Shared/#', true)),
            ...ConcreteImplementation::selectors(),
        );
    }

    private function projectors(): SelectorInterface
    {
        return Selector::AllOf(
            Selector::withFilepath('#/Infrastructure/Persistence/Projection/Projector/#', true),
            Selector::Not(Selector::withFilepath('#/Shared/#', true)),
            ...ConcreteImplementation::selectors(),
        );
    }

    private function publishers(): SelectorInterface
    {
        return Selector::AllOf(
            Selector::classname('#Publisher$#', true),
            Selector::withFilepath('#/Application/IntegrationEvent/#', true),
            Selector::Not(Selector::withFilepath('#/Shared/#', true)),
            ...ConcreteImplementation::selectors(),
        );
    }
}
