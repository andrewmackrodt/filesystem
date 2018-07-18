<?php

declare(strict_types=1);

namespace Test\Functional;

use Denimsoft\File\ImmutableFileInfo;

class ImmutableFileInfoTest extends FileInfoTestCase
{
    protected function getTestFileInfoData(string $pathname): array
    {
        $args    = [$pathname, getcwd(), [], null, false, false, false];
        $args[3] = is_link($pathname) ? readlink($pathname) : null;

        if (file_exists($pathname)) {
            $args[2] = stat($pathname);
            $args[4] = is_readable($pathname);
            $args[5] = is_writable($pathname);
            $args[6] = is_executable($pathname);
        }

        $fileInfo = new ImmutableFileInfo(...$args);

        return $this->extract($fileInfo);
    }
}
