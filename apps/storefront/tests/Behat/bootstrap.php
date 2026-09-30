<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Filesystem;

require __DIR__.'/../../../../vendor/autoload.php';

// friends-of-behat/symfony-extension boots the kernel before this project's own
// runtime entrypoint (public/index.php) ever runs, so APP_ENV has to be forced here.
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'e2e';

new Dotenv()->bootEnv(__DIR__.'/../../../../.env');

// The compiled container is read by nginx's e2e vhost too (docker/nginx/vhosts/e2e.conf),
// a different OS user than this CLI process — the cache dir has to stay world-writable.
umask(0000);

if (!getenv('CI')) {
    new Filesystem()->remove(__DIR__.'/../../../../var/cache/e2e');
}
