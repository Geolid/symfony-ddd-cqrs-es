<?php

declare(strict_types=1);

namespace Tools\PHPat;

use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Command\CommandInterface;
use Shared\Application\Query\QueryHandler;
use Shared\Application\Query\QueryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final class CommandQueryTest
{
    #[TestRule]
    public function commandsCarryOnlyNativeTypes(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(
                Selector::implements(CommandInterface::class),
                $this->notInTests(),
            ))
            ->canOnly()
            ->dependOn()
            ->classes(
                Selector::classname(CommandInterface::class),
                $this->applicationEnum(),
            )
            ->because('Dispatching it must never require knowing a type it doesn\'t own.');
    }

    #[TestRule]
    public function queriesCarryOnlyNativeTypesOrResult(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(
                Selector::implements(QueryInterface::class),
                $this->notInTests(),
            ))
            ->canOnly()
            ->dependOn()
            ->classes(
                Selector::classname(QueryInterface::class),
                Selector::AllOf(
                    Selector::classname('#Result$#', true),
                    Selector::withFilepath('#/Application/#', true),
                ),
                $this->applicationEnum(),
            )
            ->because('Asking it, and reading its answer, must never require knowing a type it doesn\'t own.');
    }

    #[TestRule]
    public function queriesNeverTouchWriteModel(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(
                Selector::withFilepath('#/Application/Query/#', true),
                Selector::Not(Selector::withFilepath('#/vendor/#', true)),
                $this->notInTests(),
            ))
            ->shouldNot()
            ->dependOn()
            ->classes(Selector::classname('#RepositoryInterface$#', true))
            ->because('CQRS keeps reads off the write side\'s own path.');
    }

    #[TestRule]
    public function handlersAreReadonly(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AnyOf(
                Selector::appliesAttribute(CommandHandler::class),
                Selector::appliesAttribute(QueryHandler::class),
            ))
            ->should()->beReadonly()
            ->because('A handler\'s instance is reused across dispatches — mutable state there is a concurrency bug.');
    }

    #[TestRule]
    public function handlersNeverUseVendorAttribute(): Rule
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
            ->because('A handler wired the wrong way never receives what it was written to handle.');
    }

    private function notInTests(): SelectorInterface
    {
        return Selector::Not(Selector::withFilepath('#/tests/#', true));
    }

    private function applicationEnum(): SelectorInterface
    {
        return Selector::AllOf(
            Selector::isEnum(),
            Selector::withFilepath('#/Application/#', true),
        );
    }
}
