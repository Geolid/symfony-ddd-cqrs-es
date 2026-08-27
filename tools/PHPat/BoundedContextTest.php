<?php

declare(strict_types=1);

namespace Tools\PHPat;

use Patchlevel\EventSourcing\Attribute\Event;
use PHPat\Selector\ClassNamespace;
use PHPat\Selector\Filepath;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

final class BoundedContextTest
{
    private const array DELIVERY_MECHANISM_VENDOR_NAMESPACES = [
        'Symfony\Component\Console',
        'Symfony\Component\Form',
        'Symfony\Component\HttpFoundation',
        'Symfony\Component\HttpKernel\Attribute',
        'Symfony\Component\Routing\Attribute',
        'Symfony\Bundle\FrameworkBundle\Controller',
        'ApiPlatform',
    ];

    #[TestRule]
    public function neverDependsOnDeliveryMechanismVendors(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(
                Selector::withFilepath('#/src/#', true),
                Selector::Not(Selector::withFilepath('#/vendor/#', true)),
                Selector::Not(Selector::withFilepath('#/apps/#', true)),
            ))
            ->shouldNot()
            ->dependOn()
            ->classes(
                ...array_map(static fn (string $namespace): ClassNamespace => Selector::inNamespace($namespace), self::DELIVERY_MECHANISM_VENDOR_NAMESPACES),
            )
            ->because('Delivery-mechanism coupling costs a Bounded Context the portability Ports & Adapters exists to give it.');
    }

    /**
     * @return iterable<string, Rule>
     */
    #[TestRule]
    public function communicatesOnlyViaIntegrationEvents(): iterable
    {
        $root = \dirname(__DIR__, 2);
        $boundedContextDirs = $this->boundedContextDirs($root);

        foreach ($boundedContextDirs as $boundedContextDir) {
            $boundedContextName = str_replace('/', '.', substr($boundedContextDir, \strlen($root.'/src/')));
            $otherBoundedContextDirs = array_values(array_diff($boundedContextDirs, [$boundedContextDir]));

            yield $boundedContextName => PHPat::rule()
                ->classes(Selector::AllOf(
                    Selector::withFilepath('#'.preg_quote(substr($boundedContextDir, \strlen($root)), '#').'/#', true),
                    Selector::Not(Selector::withFilepath('#/tests/#', true)),
                ))
                ->canOnly()
                ->dependOn()
                ->classes(
                    Selector::NoneOf(...array_map(
                        static fn (string $otherBoundedContextDir): Filepath => Selector::withFilepath('#'.preg_quote(substr($otherBoundedContextDir, \strlen($root)), '#').'/#', true),
                        $otherBoundedContextDirs,
                    )),
                    Selector::implements(IntegrationEventInterface::class),
                )
                ->because('Integration Events are this Bounded Context\'s Event-Carried State Transfer, in its own Published Language — bypassing them lets an internal change ripple into every consumer.');
        }
    }

    /**
     * @return iterable<string, Rule>
     */
    #[TestRule]
    public function domainEventsStayInternal(): iterable
    {
        $root = \dirname(__DIR__, 2);
        $boundedContextDirs = $this->boundedContextDirs($root);

        foreach ($boundedContextDirs as $boundedContextDir) {
            $boundedContextName = str_replace('/', '.', substr($boundedContextDir, \strlen($root.'/src/')));
            $boundedContextPath = substr($boundedContextDir, \strlen($root));

            yield $boundedContextName => PHPat::rule()
                ->classes(Selector::AllOf(
                    Selector::Not(Selector::withFilepath('#'.preg_quote($boundedContextPath, '#').'/#', true)),
                    Selector::Not(Selector::withFilepath('#/tests/#', true)),
                ))
                ->shouldNot()
                ->dependOn()
                ->classes(Selector::AllOf(
                    Selector::withFilepath('#'.preg_quote($boundedContextPath, '#').'/Domain/#', true),
                    Selector::appliesAttribute(Event::class),
                    Selector::Not(Selector::implements(IntegrationEventInterface::class)),
                    Selector::Not(Selector::isInterface()),
                ))
                ->because('An internal fact leaking into another Bounded Context couples the two beyond intent.');
        }
    }

    /**
     * @return list<string>
     */
    private function boundedContextDirs(string $root): array
    {
        return array_values(array_filter(
            glob($root.'/src/*/*', \GLOB_ONLYDIR) ?: [],
            static fn (string $dir): bool => 'Shared' !== basename(\dirname($dir)),
        ));
    }
}
