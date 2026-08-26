<?php

declare(strict_types=1);

namespace Tools\PHPat;

use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Command\CommandInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Port\DrivingPort;
use Shared\Application\Query\QueryInterface;
use Symfony\Component\Validator\Constraints\Compound;

final class DeliveryMechanismTest
{
    #[TestRule]
    public function onlyDependsOnOwnBoundedContextExposedSurface(): Rule
    {
        return PHPat::rule()
            ->classes($this->deliveryMechanisms())
            ->canOnly()
            ->dependOn()
            ->classes(
                Selector::appliesAttribute(DrivingPort::class),
                Selector::implements(CommandInterface::class),
                Selector::implements(QueryInterface::class),
                Selector::AllOf(Selector::classname('#Result$#', true), Selector::withFilepath('#/Application/#', true)),
                Selector::classname(ApplicationExceptionInterface::class),
                Selector::implements(ApplicationExceptionInterface::class),
                Selector::extends(\DomainException::class),
                Selector::extends(Compound::class),
                Selector::AllOf(Selector::isEnum(), Selector::withFilepath('#/Application/Status/#', true)),
                Selector::Not($this->projectCode()),
                ...$this->drivingPortOutcomeSelectors(),
            )
            ->because('Reaching past what a Bounded Context exposes bypasses or duplicates its own rules outside it.');
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
            ->because('Touching storage directly bypasses the guarantees its owning Bounded Context already provides.');
    }

    #[TestRule]
    public function providersNeverDispatchCommands(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(
                Selector::withFilepath('#/State/#', true),
                Selector::withFilepath('#Provider#', true),
                Selector::Not(Selector::withFilepath('#/vendor/#', true)),
                Selector::Not(Selector::withFilepath('#/tests/#', true)),
            ))
            ->shouldNot()
            ->dependOn()
            ->classes(Selector::classname(CommandBusInterface::class))
            ->because('Command-Query Separation requires a read to stay side-effect-free.');
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

    /**
     * @return list<SelectorInterface>
     */
    private function drivingPortOutcomeSelectors(): array
    {
        $root = \dirname(__DIR__, 2);
        /** @var array<class-string, SelectorInterface> $selectors */
        $selectors = [];

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root.'/src', \FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            \assert($file instanceof \SplFileInfo);

            if (!$file->isFile() || 'php' !== $file->getExtension()) {
                continue;
            }

            $class = str_replace('/', '\\', substr($file->getPathname(), \strlen($root.'/src/'), -4));

            if (!interface_exists($class) && !class_exists($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);

            if ([] === $reflection->getAttributes(DrivingPort::class)) {
                continue;
            }

            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                $returnType = $method->getReturnType();

                if (!$returnType instanceof \ReflectionNamedType || $returnType->isBuiltin()) {
                    continue;
                }

                $name = $returnType->getName();
                \assert('' !== $name);
                $selectors[$name] = Selector::AnyOf(Selector::classname($name), Selector::implements($name));
            }
        }

        return array_values($selectors);
    }
}
