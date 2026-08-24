<?php

declare(strict_types=1);

namespace Tools\PHPat;

use Patchlevel\EventSourcing\Attribute\OnFailed;
use Patchlevel\EventSourcing\Attribute\Subscriber;
use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Shared\Application\IntegrationEvent\Publisher;
use Shared\Application\Processor\Processor;

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

    private function subscribers(): SelectorInterface
    {
        return Selector::AllOf(
            Selector::AnyOf(
                Selector::appliesAttribute(Processor::class),
                Selector::appliesAttribute(Subscriber::class),
                Selector::appliesAttribute(Publisher::class),
            ),
            Selector::Not(Selector::withFilepath('#/vendor/#', true)),
            Selector::Not(Selector::withFilepath('#/tests/#', true)),
            Selector::Not(Selector::isAbstract()),
            Selector::Not(Selector::isInterface()),
        );
    }
}
