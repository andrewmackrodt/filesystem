<?php

declare(strict_types=1);

namespace Denimsoft\File;

use Amp\Deferred;
use Amp\File\Driver;
use Amp\Promise;

class DirectoryScanner
{
    /**
     * @var Driver
     */
    private $driver;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(Filesystem $filesystem, Driver $driver)
    {
        $this->filesystem = $filesystem;
        $this->driver     = $driver;
    }

    public function listFiles(string $pathname, bool $recursive = false): Promise
    {
        $deferred = new Deferred();

        $this->driver->scandir($pathname)
            ->onResolve(function ($error, $result) use ($pathname, $recursive, $deferred) {
                // ignore the error, e.g. uv cannot scan an empty directory
                if ( ! $result) {
                    $deferred->resolve([]);

                    return;
                }

                $this->onScandir($pathname, $recursive, $result, $deferred);
            })
        ;

        return $deferred->promise();
    }

    private function onScandir(string $basepath, bool $recursive, array $filenames, Deferred $deferred)
    {
        $pending = \count($filenames);

        /**
         * @var Node[] $nodes
         */
        $nodes = [];

        foreach ($filenames as $filename) {
            $filepath = $basepath . DIRECTORY_SEPARATOR . $filename;

            $this->driver->isdir($filepath)
                ->onResolve(function ($error, $isDir) use ($recursive, $filepath, $deferred, &$nodes, &$pending) {
                    if ($isDir) {
                        // node is a directory
                        $node = new Directory($filepath, $this->filesystem);

                        if ($recursive) {
                            $this->listFiles($filepath, true)
                                ->onResolve(function ($error, $result) use ($deferred, &$nodes, &$pending) {
                                    $nodes += $result;
                                    $pending--;
                                    $this->resolveIfNotPending($pending, $deferred, $nodes);
                                })
                            ;
                        } else {
                            $pending--;
                        }
                    } else {
                        // node is a file
                        $node = new File($filepath, $this->filesystem);
                        $pending--;
                    }

                    $nodes[$filepath] = $node;
                    $this->resolveIfNotPending($pending, $deferred, $nodes);
                })
            ;
        }
    }

    private function resolveIfNotPending(int $pending, Deferred $deferred, array $nodes)
    {
        if ($pending) {
            return;
        }

        ksort($nodes);

        $deferred->resolve($nodes);
    }
}
