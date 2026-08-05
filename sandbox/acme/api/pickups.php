<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/shared/reference.php';

fake_api_respond(['trackingNumber' => fake_api_reference('ACME-LOCAL', file_get_contents('php://input') ?: '')]);
