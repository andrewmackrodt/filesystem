<?php

declare(strict_types=1);

namespace Denimsoft\File;

use Amp\Deferred;
use Amp\File\Driver;
use Amp\Promise;

class AsyncDirectoryScanner
{
    /**
     * @var Driver
     */
    private $driver;

    /**
     * @var callable
     */
    private $fileInfoBuilder;

    /**
     * @var string
     */
    private $pathname;

    /**
     * @var bool
     */
    private $recursive;

    public function __construct(
        Driver $driver,
        callable $fileInfoBuilder,
        string $pathname,
        bool $recursive = false
    ) {
        $this->driver          = $driver;
        $this->fileInfoBuilder = $fileInfoBuilder;
        $this->pathname        = $pathname;
        $this->recursive       = $recursive;
    }

    public function listFiles(): Promise
    {
        $deferred = new Deferred();

        $this->driver->scandir($this->pathname)
            ->onResolve(function ($error, $result) use ($deferred) {
                // ignore the error, e.g. uv cannot scan an empty directory
                if ((bool) $error) {
                    $result = [];
                }

                $this->onScandir($result, $deferred);
            })
        ;

        return $deferred->promise();
    }

    private function onDir(bool $isDir, string $pathname, Deferred $deferred, array &$allFiles, int &$pending)
    {
        // not a directory
        if ( ! $isDir) {
            $pending--;

            if ($pending === 0) {
                ksort($allFiles);

                $deferred->resolve($allFiles);
            }

            return;
        }

        (new self($this->driver, $this->fileInfoBuilder, $pathname, true))
            ->listFiles()
            ->onResolve(function ($error, $result) use ($deferred, &$allFiles, &$pending) {
                $allFiles += $result;

                $pending--;

                if ($pending === 0) {
                    ksort($allFiles);

                    $deferred->resolve($allFiles);
                }
            })
        ;
    }

    private function onScandir(array $filenames, Deferred $deferred)
    {
        /**
         * @var AsyncFileInfo[] $files
         */
        $files = [];

        foreach ($filenames as $filename) {
            $pathname         = $this->pathname . DIRECTORY_SEPARATOR . $filename;
            $files[$pathname] = ($this->fileInfoBuilder)($pathname);
        }
        unset($filename);

        if ( ! $files || ! $this->recursive) {
            ksort($files);

            $deferred->resolve($files);

            return;
        }

        $allFiles = $files;
        $pending  = \count($files);

        foreach ($files as $pathname => $file) {
            $file->isDir()
                ->onResolve(function ($error, $result) use ($pathname, $deferred, &$allFiles, &$pending) {
                    $this->onDir((bool) $result, $pathname, $deferred, $allFiles, $pending);
                })
            ;
        }
        unset($pathname, $file);
    }
}
