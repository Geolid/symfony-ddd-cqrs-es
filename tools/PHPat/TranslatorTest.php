<?php

declare(strict_types=1);

namespace Tools\PHPat;

use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Shared\Infrastructure\Persistence\EventStore\Translator\AbstractIntegrationEventTranslator;
use Shared\Infrastructure\Persistence\EventStore\Translator\Translator;

final class TranslatorTest
{
    #[TestRule]
    public function extendsTheBase(): Rule
    {
        return PHPat::rule()
            ->classes($this->translators())
            ->should()->extend()
            ->classes(Selector::classname(AbstractIntegrationEventTranslator::class))
            ->because('AbstractIntegrationEventTranslator brings the persist-once append() to the event store — outside it a translator would reimplement the store wiring and drift.');
    }

    #[TestRule]
    public function appliesTheDedicatedAttribute(): Rule
    {
        return PHPat::rule()
            ->classes($this->translators())
            ->should()->applyAttribute()
            ->classes(Selector::classname(Translator::class))
            ->because('A Translator must apply #[Translator] — the raw #[Subscriber] leaves group as a free string.');
    }

    private function translators(): SelectorInterface
    {
        return Selector::AllOf(
            Selector::withFilepath('#/Infrastructure/Persistence/EventStore/Translator/#', true),
            Selector::Not(Selector::withFilepath('#/Shared/#', true)),
            Selector::Not(Selector::withFilepath('#/vendor/#', true)),
            Selector::Not(Selector::withFilepath('#/tests/#', true)),
            Selector::Not(Selector::isAbstract()),
            Selector::Not(Selector::isInterface()),
        );
    }
}
