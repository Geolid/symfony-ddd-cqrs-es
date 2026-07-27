<?php

declare(strict_types=1);

namespace Tools\PHPat;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

final class AggregateTest
{
    #[TestRule]
    public function implementsAggregateRootContracts(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::appliesAttribute(Aggregate::class))
            ->should()->implement()
            ->classes(
                Selector::classname(AggregateRoot::class),
                Selector::classname(AggregateRootMetadataAware::class),
            )
            ->because('An #[Aggregate] class is loaded through patchlevel — both contracts are required for hydration and metadata resolution.');
    }

    #[TestRule]
    public function usesAttributeBehaviour(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::appliesAttribute(Aggregate::class))
            ->should()->include()
            ->classes(Selector::classname(AggregateRootAttributeBehaviour::class))
            ->because('AggregateRootAttributeBehaviour provides recordThat()/#[Apply] dispatch — without it the contracts above are dead letters.');
    }

    #[TestRule]
    public function isNotReadonly(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::appliesAttribute(Aggregate::class))
            ->shouldNot()->beReadonly()
            ->because('Aggregate state is mutated by #[Apply] methods on replay — readonly would break rehydration.');
    }
}
