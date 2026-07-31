<?php

declare(strict_types=1);

$appId = getenv('APP_ID');
$cacheDir = \dirname(__DIR__).'/var/cache/prod'.(\is_string($appId) && '' !== $appId ? '/'.$appId : '');

if (file_exists($cacheDir.'/Bootstrap_KernelProdContainer.preload.php')) {
    require $cacheDir.'/Bootstrap_KernelProdContainer.preload.php';
}
