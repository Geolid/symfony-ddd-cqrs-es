<?php

declare(strict_types=1);

namespace Tools\PHPat;

use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Shared\Application\Attribute\AsDrivingPort;
use Shared\Application\Command\CommandInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryInterface;

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
                Selector::implements(CommandInterface::class),
                Selector::implements(QueryInterface::class),
                Selector::classname(ApplicationExceptionInterface::class),
                Selector::implements(ApplicationExceptionInterface::class),
                Selector::AllOf(
                    Selector::classname('#Result$#', true),
                    Selector::withFilepath('#/Application/#', true),
                ),
                Selector::withFilepath('#/Infrastructure/Validation/#', true),
                Selector::Not($this->projectCode()),
            )
            ->because('A Delivery Mechanism depends only on a BC exposition surface: #[AsDrivingPort] ports, the Command/Query messages, and Validation compounds.');
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
