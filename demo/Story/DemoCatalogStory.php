<?php

declare(strict_types=1);

namespace Demo\Story;

use Catalog\Tests\Listing\Support\Factory\ProductFactory;
use Shared\Tests\Support\Factory\LabelFactory;
use Support\Foundry\Story\AbstractAggregateStory;
use Zenstruck\Foundry\Attribute\AsFixture;

/**
 * The products the demo storefront lists.
 */
#[AsFixture(name: 'demo-catalog', groups: ['demo'])]
final class DemoCatalogStory extends AbstractAggregateStory
{
    private const array PRODUCTS = [
        'Espresso cup' => 1_290,
        'Teapot' => 3_490,
        'Saucer set' => 2_190,
        'Stoneware mug' => 1_590,
        'Milk jug' => 1_890,
        'Sugar bowl' => 1_490,
    ];

    public function build(): void
    {
        foreach (self::PRODUCTS as $label => $unitPriceInCents) {
            $this->store(ProductFactory::new()->withLabel(LabelFactory::new(['value' => $label])->create())->withUnitPriceInCents($unitPriceInCents)->create());
        }
    }
}
