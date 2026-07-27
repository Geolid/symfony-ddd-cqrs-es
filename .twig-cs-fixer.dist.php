<?php

declare(strict_types=1);

use TwigCsFixer\Config\Config;
use TwigCsFixer\File\Finder;

$finder = (new Finder())
    ->in([
        __DIR__.'/apps',
        __DIR__.'/ui/templates',
    ]);

return (new Config())
    ->setFinder($finder)
    ->setCacheFile(__DIR__.'/var/.twig-cs-fixer.cache');
