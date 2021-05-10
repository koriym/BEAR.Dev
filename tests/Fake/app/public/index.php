<?php

declare(strict_types=1);

use MyVendor\MyProject\Bootstrap;

require dirname(__DIR__) . '/vendor/autoload.php';
exit((new Bootstrap())(PHP_SAPI === 'cli-server' ? 'hal-app' : 'prod-hal-app', $GLOBALS, $_SERVER));
