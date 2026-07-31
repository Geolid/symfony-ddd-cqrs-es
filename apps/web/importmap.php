<?php

declare(strict_types=1);

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'shared' => [
        'path' => 'shared/shared.js',
        'entrypoint' => true,
    ],
    'app' => [
        'path' => 'web/app.js',
        'entrypoint' => true,
    ],
    '@picocss/pico' => [
        'version' => '2.1.1',
    ],
    '@picocss/pico/css/pico.min.css' => [
        'version' => '2.1.1',
        'type' => 'css',
    ],
    '@picocss/pico/css/pico.colors.min.css' => [
        'version' => '2.1.1',
        'type' => 'css',
    ],
    'alpinejs' => [
        'version' => '3.15.8',
    ],
];
