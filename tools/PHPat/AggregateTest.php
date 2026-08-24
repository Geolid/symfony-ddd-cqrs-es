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
            ->because('Event Sourcing requires an aggregate to be rebuildable from its own recorded history.');
    }

    #[TestRule]
    public function usesAttributeBehaviour(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::appliesAttribute(Aggregate::class))
            ->should()->include()
            ->classes(Selector::classname(AggregateRootAttributeBehaviour::class))
            ->because('Event Sourcing requires an aggregate to record and replay facts to rebuild its own state.');
    }
}
