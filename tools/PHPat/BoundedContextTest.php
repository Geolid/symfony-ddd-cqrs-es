<?php

declare(strict_types=1);

namespace Tools\PHPat;

use PHPat\Selector\ClassNamespace;
use PHPat\Selector\Filepath;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Shared\Application\Event\IntegrationEventInterface;

final class BoundedContextTest
{
    private const array DELIVERY_VENDOR_NAMESPACES = [
        'Symfony\Component\Console',
        'Symfony\Component\Form',
        'Symfony\Component\HttpFoundation',
        'Symfony\Component\HttpKernel\Attribute',
        'Symfony\Component\Routing\Attribute',
        'Symfony\Bundle\FrameworkBundle\Controller',
        'ApiPlatform',
    ];

    #[TestRule]
    public function neverDependsOnDeliveryVendors(): Rule
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
                ...array_map(static fn (string $namespace): ClassNamespace => Selector::inNamespace($namespace), self::DELIVERY_VENDOR_NAMESPACES),
            )
            ->because('Delivery belongs to a Delivery Mechanism, never to a BC — Infrastructure may use a framework vendor for glue (Twig, Mailer...) but never a delivery-only one.');
    }

    /**
     * @return iterable<string, Rule>
     */
    #[TestRule]
    public function onlyReachesAnotherBcThroughIntegrationEvents(): iterable
    {
        $root = \dirname(__DIR__, 2);
        $bcDirs = [
            ...glob($root.'/src/*/*', \GLOB_ONLYDIR) ?: [],
        ];
        $bcDirs = array_values(array_filter($bcDirs, static fn (string $dir): bool => 'Shared' !== basename(\dirname($dir))));

        foreach ($bcDirs as $bcDir) {
            $bcName = str_replace('/', '.', substr($bcDir, \strlen($root.'/src/')));
            $otherBcDirs = array_values(array_diff($bcDirs, [$bcDir]));

            yield $bcName => PHPat::rule()
                ->classes(Selector::AllOf(
                    Selector::withFilepath('#'.preg_quote(substr($bcDir, \strlen($root)), '#').'/#', true),
                    Selector::Not(Selector::withFilepath('#/tests/#', true)),
                ))
                ->canOnly()
                ->dependOn()
                ->classes(
                    Selector::NoneOf(...array_map(
                        static fn (string $otherBcDir): Filepath => Selector::withFilepath('#'.preg_quote(substr($otherBcDir, \strlen($root)), '#').'/#', true),
                        $otherBcDirs,
                    )),
                    Selector::implements(IntegrationEventInterface::class),
                )
                ->because('A BC reaches another BC only through its Integration Events — Deptrac allows the pair, this rule says how.');
        }
    }
}
