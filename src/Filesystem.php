<?php

declare(strict_types=1);

namespace Denimsoft\File;

use Amp\Promise;

interface Filesystem
{
    /**
     * @param string $path
     *
     * @return Promise|\SplFileInfo
     */
    public function fileinfo(string $path): Promise;

    /**
     * @param string $path
     * @param bool   $recursive
     *
     * @return Promise|\SplFileInfo[]
     */
    public function scandir(string $path, bool $recursive = false): Promise;
}
