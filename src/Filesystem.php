<?php

declare(strict_types=1);

namespace Denimsoft\File;

use Amp\File\Driver;
use Amp\Promise;
use Amp\Success;
use function Amp\File\filesystem;

class Filesystem
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
        return new Success(new AsyncFileInfo($path, $this->driver));
    }

    /**
     * @param string $path
     * @param bool   $recursive
     *
     * @return Promise|AsyncFileInfo[]
     */
    public function scandir(string $path, bool $recursive = false): Promise
    {
        return (new AsyncDirectoryScanner(
                $this->driver,
                function (string $pathname): AsyncFileInfo {
                    return new AsyncFileInfo($pathname, $this->driver);
                },
                $path,
                $recursive
            ))
            ->listFiles()
        ;
    }
}
