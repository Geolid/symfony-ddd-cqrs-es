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
use Shared\Application\Processor\Processor;
use Shared\Infrastructure\Persistence\Projection\Projector\AbstractDbalProjector;
use Shared\Infrastructure\Persistence\Projection\Projector\Projector;

final class SubscriptionTest
{
    #[TestRule]
    public function isReadonly(): Rule
    {
        return PHPat::rule()
            ->classes($this->subscribers())
            ->should()->beReadonly()
            ->because('A subscriber holds no state — a mutable one is a latent concurrency bug.');
    }

    #[TestRule]
    public function neverHandlesOwnFailure(): Rule
    {
        return PHPat::rule()
            ->classes($this->subscribers())
            ->shouldNot()->dependOn()
            ->classes(Selector::classname(OnFailed::class))
            ->because('A failure silently skipped lets state drift from the truth it was supposed to reflect, unnoticed.');
    }

    #[TestRule]
    public function processorsApplyOwnAttribute(): Rule
    {
        return PHPat::rule()
            ->classes($this->processors())
            ->should()->applyAttribute()
            ->classes(Selector::classname(Processor::class))
            ->because('A side effect never wired up silently never runs.');
    }

    #[TestRule]
    public function projectorsApplyOwnAttribute(): Rule
    {
        return PHPat::rule()
            ->classes($this->projectors())
            ->should()->applyAttribute()
            ->classes(Selector::classname(Projector::class))
            ->because('A materialized view never wired up stays silently empty.');
    }

    #[TestRule]
    public function projectorsExtendDbalBase(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf($this->projectors(), Selector::classname('#^Dbal#', true)))
            ->should()->extend()
            ->classes(Selector::classname(AbstractDbalProjector::class))
            ->because('A materialized view whose storage is never created can\'t materialize anything.');
    }

    #[TestRule]
    public function publishersApplyOwnAttribute(): Rule
    {
        return PHPat::rule()
            ->classes($this->publishers())
            ->should()->applyAttribute()
            ->classes(Selector::classname(Publisher::class))
            ->because('A fact crossing a Bounded Context boundary needs its own dedicated, guaranteed processing group — a shared one can\'t promise that.');
    }

    private function subscribers(): SelectorInterface
    {
        return Selector::AllOf(
            Selector::AnyOf(
                Selector::appliesAttribute(Processor::class),
                Selector::appliesAttribute(Projector::class),
                Selector::appliesAttribute(Publisher::class),
            ),
            Selector::Not(Selector::withFilepath('#/vendor/#', true)),
            Selector::Not(Selector::withFilepath('#/tests/#', true)),
            Selector::Not(Selector::isAbstract()),
            Selector::Not(Selector::isInterface()),
        );
    }

    private function processors(): SelectorInterface
    {
        return Selector::AllOf(
            Selector::withFilepath('#/Application/Processor/#', true),
            Selector::Not(Selector::withFilepath('#/Shared/#', true)),
            Selector::Not(Selector::withFilepath('#/vendor/#', true)),
            Selector::Not(Selector::withFilepath('#/tests/#', true)),
            Selector::Not(Selector::isAbstract()),
            Selector::Not(Selector::isInterface()),
        );
    }

    private function projectors(): SelectorInterface
    {
        return Selector::AllOf(
            Selector::withFilepath('#/Infrastructure/Persistence/Projection/Projector/#', true),
            Selector::Not(Selector::withFilepath('#/Shared/#', true)),
            Selector::Not(Selector::withFilepath('#/vendor/#', true)),
            Selector::Not(Selector::withFilepath('#/tests/#', true)),
            Selector::Not(Selector::isAbstract()),
            Selector::Not(Selector::isInterface()),
        );
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
