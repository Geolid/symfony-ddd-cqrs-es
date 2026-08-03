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
use Shared\Domain\Event\DomainEventInterface;

final class BoundaryMessageTest
{
    private const array EVENT_INTERFACES = [DomainEventInterface::class, IntegrationEventInterface::class];

    #[TestRule]
    public function commandsCarryOnlyNativeTypes(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(
                Selector::implements(CommandInterface::class),
                Selector::Not(Selector::withFilepath('#/tests/#', true)),
            ))
            ->canOnly()
            ->dependOn()
            ->classes(Selector::classname(CommandInterface::class))
            ->because('A Command carries native types only — it never returns, so it has no Result to carry; a VO or vendor type would couple both sides to internals.');
    }

    #[TestRule]
    public function queriesCarryOnlyNativeTypesOrTheirResult(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(
                Selector::implements(QueryInterface::class),
                Selector::Not(Selector::withFilepath('#/tests/#', true)),
            ))
            ->canOnly()
            ->dependOn()
            ->classes(
                Selector::classname(QueryInterface::class),
                Selector::AllOf(
                    Selector::classname('#Result$#', true),
                    Selector::withFilepath('#/Application/#', true),
                ),
            )
            ->because('A Query carries native types plus its own Result — nothing else, or a VO/vendor type couples both sides to internals.');
    }

    /**
     * @return iterable<string, Rule>
     */
    #[TestRule]
    public function eventsCarryOnlyNativeTypesOrEsMetadata(): iterable
    {
        foreach (self::EVENT_INTERFACES as $eventInterface) {
            yield $eventInterface => PHPat::rule()
                ->classes(Selector::AllOf(
                    Selector::implements($eventInterface),
                    Selector::Not(Selector::withFilepath('#/tests/#', true)),
                ))
                ->canOnly()
                ->dependOn()
                ->classes(
                    Selector::classname($eventInterface),
                    Selector::classname(Event::class),
                    Selector::classname(PersonalData::class),
                    Selector::classname(DataSubjectId::class),
                )
                ->because('An event carries native types plus patchlevel ES-metadata attributes (#[Event], #[PersonalData], #[DataSubjectId]) — nothing else, or a VO/vendor type couples both sides to internals.');
        }
    }
}
