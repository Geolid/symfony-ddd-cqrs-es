<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->in([
        __DIR__.'/apps',
        __DIR__.'/bootstrap',
        __DIR__.'/config',
        __DIR__.'/demo',
        __DIR__.'/src',
        __DIR__.'/tests',
        __DIR__.'/tools',
    ])
    ->notPath('reference.php')
    ->append([__FILE__]);

return (new PhpCsFixer\Config())
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'declare_strict_types' => true,
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
            'attribute_placement' => 'standalone',
        ],
        'multiline_promoted_properties' => [
            'minimum_number_of_parameters' => 2,
            'keep_blank_lines' => true,
        ],
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_class_elements' => [
            'order' => [
                'use_trait',
                'case',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property_public',
                'property_protected',
                'property_private',
                'construct',
                'destruct',
                'magic',
                'phpunit',
                'method_public',
                'method_public_abstract',
                'method_protected_abstract',
                'method_protected',
                'method_private',
            ],
        ],
        'php_unit_strict' => true,
        'phpdoc_line_span' => [
            'property' => 'single',
            'const' => 'single',
            'method' => 'multi',
        ],
        'phpdoc_to_comment' => [
            'ignored_tags' => ['var'],
        ],
        'strict_comparison' => true,
        'strict_param' => true,
        'trailing_comma_in_multiline' => [
            'elements' => ['arguments', 'arrays', 'match', 'parameters'],
        ],
    ])
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setCacheFile(__DIR__.'/var/.php-cs-fixer.cache');
