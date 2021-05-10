<?php

$appDir = dirname(__DIR__);
passthru(sprintf('cd %s/tests/Fake/app', $appDir)
    . ' && composer config scripts.bin "echo installing"'
    . ' && composer update --no-dev'
);

echo 'setup done.' . PHP_EOL;
