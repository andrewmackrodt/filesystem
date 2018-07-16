<?php

declare(strict_types=1);

namespace Denimsoft\File;

use Amp\File\Driver;
use Amp\Promise;
use function Amp\call;
use function Amp\File\filesystem;
use function Amp\Promise\any;

class ImmutableFilesystem implements Filesystem
{
    /**
     * @var Driver
     */
    private $driver;

    public function __construct(Driver $driver = null)
    {
        $this->driver = $driver ?? filesystem();
    }

    /**
     * @param string $path
     *
     * @return Promise|ImmutableFileInfo
     */
    public function fileinfo(string $path): Promise
    {
        return call(function () use ($path) {
            $fileStat = yield $this->driver->stat($path);
            $linkStat = yield $this->driver->lstat($path);
            $linkTarget = null;

            if ([$linkStat['dev'], $linkStat['ino']] !== [$fileStat['dev'], $fileStat['ino']]) {
                $linkTarget = yield $this->driver->readlink($path);
            }

            return new ImmutableFileInfo($path, getcwd(), $fileStat, $linkTarget);
        });
    }

    /**
     * @param string $path
     * @param bool   $recursive
     *
     * @return Promise|ImmutableFileInfo[]
     */
    public function scandir(string $path, bool $recursive = false): Promise
    {
        return call(function () use ($path, $recursive) {
            $promises = [$path => $this->fileinfo($path)];

            foreach (yield $this->driver->scandir($path) as $filename) {
                $pathname = $path . DIRECTORY_SEPARATOR . $filename;
                $promises[$pathname] = $this->fileinfo($pathname);
            }

            /** @var ImmutableFileInfo[] $files */
            $files = (yield any($promises))[1];

            if ( ! $recursive) {
                ksort($files);

                return $files;
            }

            $promises = [];

            foreach (array_slice($files, 1) as $file) {
                if ($file->isDir()) {
                    $promises[] = $this->scandir($file->getPathname(), true);
                }
            }

            if ($promises) {
                $files = array_merge($files, ...(yield any($promises))[1]);

                unset($promises);
            }

            ksort($files);

            return $files;
        });
    }
}
