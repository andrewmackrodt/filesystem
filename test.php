<?php

declare(strict_types=1);

use Amp\File\EioDriver;
use Amp\Loop;
use Denimsoft\File\AsyncFilesystem;
use Denimsoft\File\ImmutableFilesystem;
use const Amp\File\LOOP_STATE_IDENTIFIER;

require_once __DIR__ . '/vendor/autoload.php';

if (\extension_loaded('eio')) {
    $driver = new EioDriver();

    Loop::setState(LOOP_STATE_IDENTIFIER, $driver);
}

Loop::run(function () {
    $startedAt = microtime(true);
    $immutableFilesystem = new ImmutableFilesystem();
    $immutableFiles = yield $immutableFilesystem->scandir(__DIR__, true);
    echo number_format(microtime(true) - $startedAt, 3) . " seconds\n";

    $startedAt = microtime(true);
    $asyncFilesystem = new AsyncFilesystem();
    $asyncFiles = yield $asyncFilesystem->scandir(__DIR__, true);
    echo number_format(microtime(true) - $startedAt, 3) . " seconds\n";

    echo print_r('', true);
});
