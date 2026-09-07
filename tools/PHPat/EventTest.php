<?php

declare(strict_types=1);

namespace Tools\PHPat;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;
use Shared\Domain\Gdpr\ErasedFieldSentinel;
use Shared\Domain\Gdpr\ErasedValueObjectSentinel;

final class EventTest
{
    #[TestRule]
    public function areReadonly(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(
                Selector::appliesAttribute(Event::class),
                Selector::Not(Selector::isInterface()),
                $this->notInTests(),
            ))
            ->should()->beReadonly()
            ->because('An event is an immutable fact — once recorded or published, it never changes.');
    }

    #[TestRule]
    public function domainEventsCarryOnlyNativeTypesOrEsMetadata(): Rule
    {
        return PHPat::rule()
            ->classes($this->domainEvents())
            ->canOnly()
            ->dependOn()
            ->classes(
                Selector::AllOf(
                    Selector::isEnum(),
                    Selector::withFilepath('#/Domain/#', true),
                ),
                Selector::AllOf(
                    Selector::Not(Selector::isEnum()),
                    Selector::withFilepath('#/ValueObject/#', true),
                ),
                Selector::classname(ErasedValueObjectSentinel::class),
                ...$this->esMetadataSelectors(),
            )
            ->because('A recorded fact must decode forever despite other types changing, and its personal data must stay erasable without rewriting history.');
    }

    #[TestRule]
    public function integrationEventsImplementContract(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(
                Selector::classname('#IntegrationEvent$#', true),
                Selector::withFilepath('#/Application/IntegrationEvent/#', true),
                Selector::Not(Selector::isInterface()),
                $this->notInTests(),
            ))
            ->should()->implement()
            ->classes(Selector::classname(IntegrationEventInterface::class))
            ->because('Publishing a fact needs a reliable, checkable shape — without one, nothing tells a real fact from anything else.');
    }

    #[TestRule]
    public function integrationEventsCarryOnlyNativeTypesOrEsMetadata(): Rule
    {
        return PHPat::rule()
            ->classes($this->integrationEvents())
            ->canOnly()
            ->dependOn()
            ->classes(
                Selector::classname(IntegrationEventInterface::class),
                Selector::AllOf(
                    Selector::isEnum(),
                    Selector::withFilepath('#/Application/#', true),
                ),
                ...$this->esMetadataSelectors(),
            )
            ->because('A Published Language must decode forever regardless of other types; its erasure already happened at the source, redoing it here duplicates that fact.');
    }

    private function notInTests(): SelectorInterface
    {
        return Selector::Not(Selector::withFilepath('#/tests/#', true));
    }

    private function domainEvents(): SelectorInterface
    {
        return Selector::AllOf(
            Selector::appliesAttribute(Event::class),
            Selector::Not(Selector::implements(IntegrationEventInterface::class)),
            Selector::Not(Selector::isInterface()),
            $this->notInTests(),
        );
    }

    private function integrationEvents(): SelectorInterface
    {
        return Selector::AllOf(
            Selector::appliesAttribute(Event::class),
            Selector::implements(IntegrationEventInterface::class),
            $this->notInTests(),
        );
    }

    /**
     * @return list<SelectorInterface>
     */
    private function esMetadataSelectors(): array
    {
        return [
            Selector::classname(Event::class),
            Selector::classname(SensitiveData::class),
            Selector::classname(DataSubjectId::class),
            Selector::classname(ErasedFieldSentinel::class),
        ];
    }
}
