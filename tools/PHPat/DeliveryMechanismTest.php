<?php

declare(strict_types=1);

namespace Tools\PHPat;

use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Shared\Application\Language\PublishedLanguageInterface;
use Shared\Application\Port\AsDrivingPort;

final class DeliveryMechanismTest
{
    #[TestRule]
    public function onlyDependsOnDrivingPorts(): Rule
    {
        return PHPat::rule()
            ->classes($this->deliveryMechanisms())
            ->canOnly()
            ->dependOn()
            ->classes(
                Selector::appliesAttribute(AsDrivingPort::class),
                Selector::classname(PublishedLanguageInterface::class),
                Selector::implements(PublishedLanguageInterface::class),
                Selector::Not($this->projectCode()),
            )
            ->because('A Delivery Mechanism touches only a BC Open Host Service: its #[AsDrivingPort] behaviours, and its published language — Command/Query messages, Results, application failures, published vocabularies and accepted input shapes, all carrying PublishedLanguageInterface.');
    }

    #[TestRule]
    public function neverTouchesPersistence(): Rule
    {
        return PHPat::rule()
            ->classes($this->deliveryMechanisms())
            ->shouldNot()
            ->dependOn()
            ->classes(
                Selector::inNamespace('Doctrine\DBAL'),
                Selector::inNamespace('Doctrine\ORM'),
                Selector::inNamespace('Doctrine\Persistence'),
                Selector::inNamespace('Patchlevel\EventSourcing'),
            )
            ->because('A Delivery Mechanism never reaches storage — reads go through a Query, writes through a Command.');
    }

    private function deliveryMechanisms(): SelectorInterface
    {
        return Selector::AllOf(
            Selector::withFilepath('#/apps/#', true),
            Selector::Not(Selector::withFilepath('#/tests/#', true)),
        );
    }

    private function projectCode(): SelectorInterface
    {
        return Selector::AllOf(
            Selector::Not(Selector::withFilepath('#/vendor/#', true)),
            Selector::Not(Selector::withFilepath('#/apps/#', true)),
        );
    }
}
