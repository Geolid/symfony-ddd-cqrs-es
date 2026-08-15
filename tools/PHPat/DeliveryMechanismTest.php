<?php

declare(strict_types=1);

namespace Tools\PHPat;

use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Shared\Application\Command\CommandInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Port\AsDrivingPort;
use Shared\Application\Port\DrivingPortOutcomeInterface;
use Shared\Application\Query\QueryInterface;
use Shared\Application\Result\ResultInterface;
use Symfony\Component\Validator\Constraints\Compound;

final class DeliveryMechanismTest
{
    #[TestRule]
    public function onlyDependsOnTheOpenHostService(): Rule
    {
        return PHPat::rule()
            ->classes($this->deliveryMechanisms())
            ->canOnly()
            ->dependOn()
            ->classes(
                Selector::appliesAttribute(AsDrivingPort::class),
                Selector::implements(CommandInterface::class),
                Selector::implements(QueryInterface::class),
                Selector::implements(ResultInterface::class),
                Selector::classname(ApplicationExceptionInterface::class),
                Selector::implements(ApplicationExceptionInterface::class),
                Selector::extends(\DomainException::class),
                Selector::extends(Compound::class),
                Selector::implements(DrivingPortOutcomeInterface::class),
                Selector::AllOf(Selector::isEnum(), Selector::withFilepath('#/Application/Status/#', true)),
                Selector::Not($this->projectCode()),
            )
            ->because('A Delivery Mechanism touches only a BC Open Host Service: its #[AsDrivingPort] behaviours and their outcomes, its Command/Query/Result/Exception vocabulary, its validation compounds, its Application/Status <X>Status vocabulary, and \DomainException — a business failure bubbling from the aggregate that owns it, generic or concrete alike.');
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
