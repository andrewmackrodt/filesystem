<?php

declare(strict_types=1);

use Amp\File\EioDriver;
use Amp\Loop;
use Denimsoft\File\Filesystem;
use const Amp\File\LOOP_STATE_IDENTIFIER;

require_once __DIR__ . '/vendor/autoload.php';

if (\extension_loaded('eio')) {
    $driver = new EioDriver();

    Loop::setState(LOOP_STATE_IDENTIFIER, $driver);
}

function getFilesBlocking(string $path): array
{
    $files = [];

    /* @var SplFileInfo[] $dirFiles */
    $dirFiles = iterator_to_array(
        new RecursiveDirectoryIterator(
            $path,
            RecursiveDirectoryIterator::SKIP_DOTS
        )
    );

    foreach ($dirFiles as $file) {
        $files[$file->getPathname()] = $file;
    }

    foreach ($dirFiles as $file) {
        if ( ! $file->isDir() || $file->isLink()) {
            continue;
        }

        $files = array_replace($files, getFilesBlocking($file->getPathname()));
    }

    ksort($files);

    return $files;
}

Loop::run(function () {
    $startedAt = microtime(true);
    $blockingFiles = getFilesBlocking(__DIR__);
    echo number_format(microtime(true) - $startedAt, 3) . " seconds\n";

    $startedAt = microtime(true);
    $asyncFilesystem = new Filesystem();
    $asyncFiles = yield $asyncFilesystem->scandir(__DIR__, true);
    echo number_format(microtime(true) - $startedAt, 3) . " seconds\n";

    $n = '';
//    echo print_r(Amp\File\StatCache::get(__DIR__ . '/composer.json'), true);
});
