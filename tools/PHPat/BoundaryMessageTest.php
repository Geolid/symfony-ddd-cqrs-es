<?php

declare(strict_types=1);

namespace Tools\PHPat;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Shared\Application\Command\CommandInterface;
use Shared\Application\Event\IntegrationEventInterface;
use Shared\Application\Query\QueryInterface;

final class BoundaryMessageTest
{
    #[TestRule]
    public function commandsAndQueriesCarryOnlyNativeTypes(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(
                Selector::AnyOf(
                    Selector::implements(CommandInterface::class),
                    Selector::implements(QueryInterface::class),
                ),
                Selector::Not(Selector::withFilepath('#/tests/#', true)),
            ))
            ->canOnly()
            ->dependOn()
            ->classes(
                Selector::classname(CommandInterface::class),
                Selector::classname(QueryInterface::class),
                // A Query documents its return contract: @implements QueryInterface<...Result>.
                Selector::AllOf(
                    Selector::classname('#Result$#', true),
                    Selector::withFilepath('#/Application/#', true),
                ),
            )
            ->because('A Command/Query crossing a boundary carries native types only — a VO or vendor type would couple both sides to internals.');
    }

    #[TestRule]
    public function integrationEventsCarryOnlyNativeTypes(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(
                Selector::implements(IntegrationEventInterface::class),
                Selector::Not(Selector::withFilepath('#/tests/#', true)),
            ))
            ->canOnly()
            ->dependOn()
            ->classes(
                Selector::classname(IntegrationEventInterface::class),
                // Inert ES-metadata attribute, read only by reflection — no vendor runtime
                // dependency: #[Event] gives the store its serialization identity.
                Selector::classname('Patchlevel\EventSourcing\Attribute\Event'),
            )
            ->because('An Integration Event may additionally carry the patchlevel #[Event] ES-metadata attribute for its event-store serialization identity — no other vendor dependency is allowed.');
    }
}
