<?php

declare(strict_types=1);

namespace Tools\PHPat;

use Patchlevel\EventSourcing\Attribute\OnFailed;
use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\IntegrationEvent\Publisher;
use Shared\Application\Policy;
use Shared\Infrastructure\Projection\Projector;
use Tools\PHPat\Selector\ConcreteImplementation;
use Tools\PHPat\Selector\DependsOnClass;

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
    public function neverHandlesOwnFailureWithoutCompensation(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(
                $this->subscribers(),
                Selector::Not(new DependsOnClass(CommandBusInterface::class)),
            ))
            ->shouldNot()->dependOn()
            ->classes(Selector::classname(OnFailed::class))
            ->because('A silently skipped failure must still dispatch a compensating command — logging alone lets state drift from the truth it should reflect.');
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
            ->because('Publishing needs its own group — sharing Policy\'s or Projector\'s would break store()\'s guarantee of when a fact actually crosses the boundary.');
    }

    private function subscribers(): SelectorInterface
    {
        return Selector::AllOf(
            Selector::AnyOf(
                Selector::appliesAttribute(Policy::class),
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
