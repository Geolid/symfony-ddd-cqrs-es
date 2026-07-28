<?php

declare(strict_types=1);

namespace Tools\PHPat;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Attribute\PersonalData;
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
    public function integrationEventsCarryOnlyNativeTypesOrEsMetadata(): Rule
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
                // Inert ES-metadata attributes, read only by reflection — no vendor
                // runtime dependency: #[Event] gives the store its serialization
                // identity, #[PersonalData]/#[DataSubjectId] drive crypto-shredding.
                Selector::classname(Event::class),
                Selector::classname(PersonalData::class),
                Selector::classname(DataSubjectId::class),
            )
            ->because('An Integration Event may additionally carry patchlevel ES-metadata attributes: #[Event] for its event-store serialization identity, #[PersonalData]/#[DataSubjectId] for crypto-shredding of its PII — no other vendor dependency is allowed.');
    }
}
