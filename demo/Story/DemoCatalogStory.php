<?php

declare(strict_types=1);

namespace Demo\Story;

use Catalog\Tests\Listing\Support\Factory\ProductFactory;
use Support\Foundry\Story\AbstractAggregateStory;
use Zenstruck\Foundry\Attribute\AsFixture;

#[AsFixture(name: 'demo-catalog', groups: ['demo'])]
final class DemoCatalogStory extends AbstractAggregateStory
{
    public function build(): void
    {
        $this->store(...ProductFactory::new()->many(6)->create());
    }
}
