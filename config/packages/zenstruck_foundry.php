<?php

declare(strict_types=1);

use Faker\Generator;
use Support\Foundry\EventSourcingResetter;
use Support\Foundry\FoundryFaker;
use Support\Foundry\Story\BuilderShopperStory;
use Support\Foundry\Story\ShopperStory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Zenstruck\Foundry\ORM\ResetDatabase\OrmResetter;

return static function (ContainerConfigurator $container): void {
    if (in_array($container->env(), ['dev', 'demo', 'test'], true)) {
        $container->services()
            ->set('foundry.faker', Generator::class)
            ->factory(FoundryFaker::create(...))

            ->set(ShopperStory::class)->autowire()->autoconfigure()
            ->set(BuilderShopperStory::class)->autowire()->autoconfigure()
            ->load('Iam\Tests\Support\Story\\', '%kernel.project_dir%/tests/Iam/Support/Story/*Story.php')->autowire()->autoconfigure()

            ->set(EventSourcingResetter::class)->decorate(OrmResetter::class);

        $container->extension('zenstruck_foundry', [
            'faker' => ['service' => 'foundry.faker'],
        ]);
    }
};
