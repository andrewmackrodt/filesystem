<?php

declare(strict_types=1);

use Amp\Loop;
use Denimsoft\File\Filesystem;
use Denimsoft\File\Node;
use const Amp\File\LOOP_STATE_IDENTIFIER;

require_once __DIR__ . '/vendor/autoload.php';

$eventLoop = Loop::get();

/*
 * EIO should be preferred as it does not cause crashes when attempting readlink on an invalid path
 * nor does it error when attempting to scandir an empty directory, however, it appears to be
 * blocking, making standard PHP functions better.
 */
if (getenv('EIO_ENABLED') === '1') {
    $driver = new \Amp\File\EioDriver();

    $eventLoop->setState(LOOP_STATE_IDENTIFIER, $driver);
}

Loop::run(function () use ($eventLoop) {
    $filesystem = new Filesystem();
    $i = 0;
    echo 'Starting [' . preg_replace('/^.+\\\/', '', get_class($eventLoop->getState(LOOP_STATE_IDENTIFIER))) . "] ...\n\n";

    $timer = $eventLoop->repeat(10, function () use ($eventLoop, &$i, &$files) {
        $i++;

        if ($files) {
            return;
        }

        if ($i > 100) {
            fwrite(STDERR, "Error: timeout, aborting ...\n");

            $eventLoop->stop();

            return;
        }

        echo '... 10 ms timer (' . number_format(microtime(true), 6, '', '') . ") ...\n";
    });

    $startedAt = microtime(true);
    $files = yield $filesystem->scandir('.', true);
    $eventLoop->unreference($timer);

    echo "\n";
    echo 'Found ' . count($files) . ' files in ' . number_format(microtime(true) - $startedAt, 3) . " seconds\n\n";

    /* @var Node $file */
    echo "First 10 files:\n";
    foreach (array_slice($files, 0, 10) as $file) {
        echo "  {$file->pathname} : " , (yield $file->size()), "\n";
    }

    if ($i === 0) {
        fwrite(STDERR, "Error: file scanning was blocking\n");
    }
});
