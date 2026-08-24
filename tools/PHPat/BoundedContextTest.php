<?php

declare(strict_types=1);

namespace Tools\PHPat;

use PHPat\Selector\ClassNamespace;
use PHPat\Selector\Filepath;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;
use Tools\PHPat\Helpers\BcDirs;

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
            ->because('A Bounded Context coupled to its delivery mechanism loses its Hexagonal portability.');
    }

    /**
     * @return iterable<string, Rule>
     */
    #[TestRule]
    public function communicatesOnlyViaIntegrationEvents(): iterable
    {
        $root = \dirname(__DIR__, 2);
        $bcDirs = BcDirs::all($root);

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
                ->because('This is a Bounded Context\'s Open Host Service — a shared, uniform protocol for every consumer; a bespoke integration per consumer would mean an internal change ripples across each one.');
        }
    }
}
