<?php

declare(strict_types=1);

use MyVendor\MyProject\Bootstrap;

require dirname(__DIR__) . '/Fake/app/vendor/autoload.php';

exit((new Bootstrap())('prod-hal-app', $GLOBALS, $_SERVER));
