<?php

declare(strict_types=1);

namespace Tools\PHPat;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Shared\Application\Command\ActingIdentityAware;
use Shared\Application\Command\CommandInterface;
use Shared\Application\Event\IntegrationEventInterface;
use Shared\Application\Query\QueryInterface;
use Shared\Domain\Event\DomainEventInterface;
use Shared\Domain\Gdpr\DataSubjectErasureInterface;
use Shared\Domain\Gdpr\ErasedFieldSentinel;

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
            ->classes(Selector::classname(CommandInterface::class), Selector::classname(ActingIdentityAware::class))
            ->because('A Command carries native types only — it never returns, so it has no Result to carry; a VO or vendor type would couple both sides to internals. ActingIdentityAware is a pure marker (no field of its own) naming which already-native field is the acting identity, so it stays allowed alongside CommandInterface.');
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
            $allowed = [
                Selector::classname($eventInterface),
                Selector::classname(Event::class),
                Selector::classname(SensitiveData::class),
                Selector::classname(DataSubjectId::class),
                Selector::classname(ErasedFieldSentinel::class),
            ];

            if (DomainEventInterface::class === $eventInterface) {
                $allowed[] = Selector::classname(DataSubjectErasureInterface::class);
            }

            yield $eventInterface => PHPat::rule()
                ->classes(Selector::AllOf(
                    Selector::implements($eventInterface),
                    Selector::Not(Selector::withFilepath('#/tests/#', true)),
                ))
                ->canOnly()
                ->dependOn()
                ->classes(...$allowed)
                ->because('An event carries native types plus patchlevel ES-metadata attributes (#[Event], #[SensitiveData], #[DataSubjectId], the erasure fallback sentinel) and, if it erases personal data at its own origin, the DataSubjectErasureInterface marker — a Translator always derives an Integration Event from an already-marked Domain Event, so the marker never belongs on the Integration Event side too, or the two GDPR-wide Processors subscribed to it react twice to the same fact.');
        }
    }
}
