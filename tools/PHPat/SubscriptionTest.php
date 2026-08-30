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
use Shared\Application\Policy;
use Shared\Application\Processor;
use Shared\Infrastructure\Projection\Projector;
use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;
use Tools\PHPat\Selector\ConcreteImplementation;

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
            ->because('A technical reaction to an event never wired up silently never fires.');
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
            ->because('Publishing needs its own group — sharing Policy\'s, Processor\'s, or Projector\'s would break store()\'s guarantee of when a fact actually crosses the boundary.');
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
            Selector::classname('#Processor$#', true),
            Selector::Not(Selector::classname(Processor::class)),
            Selector::Not(Selector::implements(EnvVarProcessorInterface::class)),
            Selector::Not(Selector::withFilepath('#/apps/#', true)),
            ...ConcreteImplementation::selectors(),
        );
    }

    private function projectors(): SelectorInterface
    {
        return Selector::AllOf(
            Selector::withFilepath('#/Infrastructure/Projection/Projector/#', true),
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
