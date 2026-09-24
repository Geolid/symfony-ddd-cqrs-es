<?php

declare(strict_types=1);

namespace Ui\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'ui:FormError', template: '@ui/components/FormError.html.twig')]
final class FormError
{
}
