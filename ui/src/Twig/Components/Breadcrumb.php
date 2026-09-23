<?php

declare(strict_types=1);

namespace Ui\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'ui:Breadcrumb', template: '@ui/components/Breadcrumb.html.twig')]
final class Breadcrumb
{
    /** @var list<array{label: string, url?: string}> */
    public array $items = [];
}
