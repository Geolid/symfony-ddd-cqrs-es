<?php

declare(strict_types=1);

namespace Tools\PHPat;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Shared\Application\Command\AsCommandHandler;
use Shared\Application\Query\AsQueryHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final class HandlerTest
{
    #[TestRule]
    public function isReadonly(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AnyOf(
                Selector::appliesAttribute(AsCommandHandler::class),
                Selector::appliesAttribute(AsQueryHandler::class),
            ))
            ->should()->beReadonly()
            ->because('A handler holds no state — a mutable one is a latent concurrency bug.');
    }

    #[TestRule]
    public function neverUsesFrameworkHandlerAttribute(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(
                Selector::AnyOf(
                    Selector::withFilepath('#/src/#', true),
                    Selector::withFilepath('#/apps/#', true),
                ),
                Selector::Not(Selector::withFilepath('#/vendor/#', true)),
            ))
            ->shouldNot()
            ->dependOn()
            ->classes(Selector::classname(AsMessageHandler::class))
            ->because('RegisterMessageBusHandlersPass only wires #[AsCommandHandler]/#[AsQueryHandler] — the framework attribute lands on the default bus, silently.');
    }
}
