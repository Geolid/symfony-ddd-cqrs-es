<?php

declare(strict_types=1);

namespace Tools\PHPat;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

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
                // Narrowly "src/": demo/ legitimately uses Console Commands, and apps/*/src/
                // (a "/src/" filepath match too) is exactly where delivery vendors belong.
                Selector::withFilepath('#/src/#', true),
                Selector::Not(Selector::withFilepath('#/vendor/#', true)),
                Selector::Not(Selector::withFilepath('#/apps/#', true)),
            ))
            ->shouldNot()
            ->dependOn()
            ->classes(
                ...array_map(static fn (string $namespace) => Selector::inNamespace($namespace), self::DELIVERY_VENDOR_NAMESPACES),
            )
            ->because('Delivery belongs to a Delivery Mechanism, never to a BC — Infrastructure may use a framework vendor for glue (Twig, Mailer...) but never a delivery-only one.');
    }
}
