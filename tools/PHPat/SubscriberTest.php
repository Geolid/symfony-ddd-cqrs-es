<?php

declare(strict_types=1);

namespace Tools\PHPat;

use Patchlevel\EventSourcing\Attribute\OnFailed;
use Patchlevel\EventSourcing\Attribute\Processor;
use Patchlevel\EventSourcing\Attribute\Subscriber;
use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Shared\Infrastructure\Persistence\EventStore\Translator\AbstractIntegrationEventTranslator;
use Shared\Infrastructure\Persistence\EventStore\Translator\Translator;

final class SubscriberTest
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
    public function neverHandlesItsOwnFailure(): Rule
    {
        return PHPat::rule()
            ->classes($this->subscribers())
            ->shouldNot()->dependOn()
            ->classes(Selector::classname(OnFailed::class))
            ->because('#[OnFailed] turns retry-exhaustion into skip-and-continue — a failure must halt the subscription (Status::Failed), not silently move on.');
    }

    #[TestRule]
    public function translatorsExtendTheBase(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(
                Selector::withFilepath('#/Infrastructure/Persistence/EventStore/Translator/#', true),
                Selector::Not(Selector::withFilepath('#/Shared/#', true)),
                Selector::Not(Selector::withFilepath('#/vendor/#', true)),
                Selector::Not(Selector::withFilepath('#/tests/#', true)),
                Selector::Not(Selector::isAbstract()),
                Selector::Not(Selector::isInterface()),
            ))
            ->should()->extend()
            ->classes(Selector::classname(AbstractIntegrationEventTranslator::class))
            ->because('AbstractIntegrationEventTranslator brings the persist-once append() to the event store — outside it a translator would reimplement the store wiring and drift.');
    }

    #[TestRule]
    public function translatorsApplyTheDedicatedAttribute(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(
                Selector::extends(AbstractIntegrationEventTranslator::class),
                Selector::Not(Selector::withFilepath('#/vendor/#', true)),
                Selector::Not(Selector::withFilepath('#/tests/#', true)),
                Selector::Not(Selector::isAbstract()),
            ))
            ->should()->applyAttribute()
            ->classes(Selector::classname(Translator::class))
            ->because('A Translator must apply #[Translator] — the raw #[Subscriber] leaves group as a free string.');
    }

    private function subscribers(): SelectorInterface
    {
        return Selector::AllOf(
            Selector::AnyOf(
                Selector::appliesAttribute(Processor::class),
                Selector::appliesAttribute(Subscriber::class),
                Selector::appliesAttribute(Translator::class),
            ),
            Selector::Not(Selector::withFilepath('#/vendor/#', true)),
            Selector::Not(Selector::withFilepath('#/tests/#', true)),
            Selector::Not(Selector::isAbstract()),
            Selector::Not(Selector::isInterface()),
        );
    }
}
