<?php

declare(strict_types=1);

namespace Tools\PHPat;

use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Shared\Application\Event\IntegrationEventInterface;

final class IntegrationEventTest
{
    #[TestRule]
    public function implementsItsContract(): Rule
    {
        return PHPat::rule()
            ->classes($this->integrationEvents())
            ->should()->implement()
            ->classes(Selector::classname(IntegrationEventInterface::class))
            ->because('Without IntegrationEventInterface the event escapes async routing, listener wiring and BoundaryMessageTest.');
    }

    #[TestRule]
    public function isReadonly(): Rule
    {
        return PHPat::rule()
            ->classes($this->integrationEvents())
            ->should()->beReadonly()
            ->because('An Integration Event is a published fact — mutable in-flight on an async bus otherwise.');
    }

    private function integrationEvents(): SelectorInterface
    {
        return Selector::AllOf(
            Selector::withFilepath('#/Application/Event/#', true),
            // Shared/Application/Event/ holds the contracts themselves, not business events.
            Selector::Not(Selector::withFilepath('#/src/Shared/Application/Event/#', true)),
            Selector::Not(Selector::withFilepath('#/vendor/#', true)),
            Selector::Not(Selector::withFilepath('#/tests/#', true)),
            Selector::Not(Selector::isInterface()),
        );
    }
}
