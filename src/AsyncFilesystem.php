<?php

declare(strict_types=1);

namespace Denimsoft\File;

use Amp\File\Driver;
use Amp\Promise;
use Amp\Success;
use function Amp\call;
use function Amp\File\filesystem;
use function Amp\Promise\any;

class AsyncFilesystem implements Filesystem
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
     * @return Promise|AsyncFileInfo
     */
    public function fileinfo(string $path): Promise
    {
        return new Success($this->fileinfo($path));
    }

    /**
     * @param string $path
     * @param bool   $recursive
     *
     * @return Promise|AsyncFileInfo[]
     */
    public function scandir(string $path, bool $recursive = false): Promise
    {
        return call(function () use ($path, $recursive) {
            $files = [$path => $this->_fileInfo($path)];

            foreach (yield $this->driver->scandir($path) as $filename) {
                $pathname = $path . DIRECTORY_SEPARATOR . $filename;
                $files[$pathname] = $this->_fileInfo($pathname);
            }

            if ( ! $recursive) {
                ksort($files);

                return $files;
            }

            $directories = array_keys(array_filter((yield any(array_map(
                function (AsyncFileInfo $fileInfo) {
                    return $fileInfo->isDir();
                },
                array_slice($files, 1)
            )))[1]));

            $promises = [];

            foreach ($directories as $directory) {
                $promises[] = $this->scandir($directory, true);
            }

            if ($promises) {
                $files = array_merge($files, ...(yield any($promises))[1]);

                unset($promises);
            }

            ksort($files);

            return $files;
        });
    }

    private function _fileInfo(string $path): AsyncFileInfo
    {
        return new AsyncFileInfo($path, $this->driver);
    }
}
